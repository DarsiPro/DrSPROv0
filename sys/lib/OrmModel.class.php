<?php
/**
* @project    DarsiPro CMS
* @package    OrmModel class
* @url        https://darsi.pro
*/


/**
 * Base class OrmModel. He is parent for all models.
 * Also he is something like DataMapper and simple Model.
 */
abstract class OrmModel
{
    /**
     * @var string
     */
    public $Table;


    /**
     * @var array
     */
    protected $BindedParams;



    /**
     *
     */
    public function __construct()
    {
        
    }


    public function getTable() {
        return $this->Table;
    }



    public function getTotal($params = array())
    {
        $cnt = getDB()->select($this->Table, DB_COUNT, $params);
        return $cnt;
    }




    /**
     * @param $id
     * @return bool
     */
    public function getById($id)
    {
        $entities = getDB()->select($this->Table, DB_FIRST, array(
            'cond' => array(
                'id' => $id
            )
        ));

        if ($entities && count($entities)) {
            $entities = $this->getAllAssigned($entities);
            $entityClassName = \OrmManager::getEntityNameFromModel(get_class($this));
            $entity = new $entityClassName($entities[0]);
            return (!empty($entity)) ? $entity : false;
        }
        return false;
    }




    /**
     * TODO: for old versions
     * @return mixed
     */
    protected function getDbDriver()
    {
        return getDB();
    }




    /**
     * @param $records
     * @return bool
     */
    protected function getAllAssigned($records)
    {
        if (empty($records) || count($records) < 1) return false;
        if (empty($this->BindedParams) || !is_array($this->BindedParams)) return $records;

        
        // Get all IDs from records
        $ids = array();
        // Get all foreignKeys values from records, assoc with model names
        $hasKeys = array();

        // In this place we try get all assigned data by current entities
        // further we merge this data with records(entities)
        if (!empty($this->RelatedEntities)) {
            foreach ($this->RelatedEntities as $relKey => $relVal) {
                if (!$this->isBindModel($relKey)) continue;
                
                $foreignKey = $relVal['foreignKey'];
                
                // Если указано поле основной таблицы
                $is_this_table = (false !== (strpos($foreignKey, 'this.')));

                if ($is_this_table) {
                    $foreignKey = str_replace('this.', '', $foreignKey);
                }
                
                $model = $relVal['model'];
                // For dynamical model name
                $is_dynamical_model = (false !== strpos($relVal['model'], 'this.'));
                if ($is_dynamical_model) {
                    $keyWithModel = str_replace('this.', '', $relVal['model']);
                }
                
                // Generate ids for binded models
                foreach ($records as $k => $r) {
                    // All id from records
                    $ids[] = $r['id'];
                    
                    // get dynamical model name
                    if ($is_dynamical_model)
                        $model = $r[$keyWithModel];
                    
                    // Create arrays
                    if (!array_key_exists($relKey, $hasKeys)) $hasKeys[$relKey] = array();
                    if (!array_key_exists($model, $hasKeys[$relKey])) $hasKeys[$relKey][$model] = array();
                    
                    // Save all foreignKeys values from records, assoc with model names
                    if (!empty($r[$foreignKey])) {
                        if ($relVal['type'] === 'has_many')
                            $hasKeys[$relKey][$model] = array_merge($hasKeys[$relKey][$model], explode(",",(string)($r[$foreignKey])));
                        else // if ($relVal['type'] === 'has_many')
                            $hasKeys[$relKey][$model][] = $r[$foreignKey];
                    }
                }
                
                
                // Если мы уже загружали данные прикрепленных биндов,
                $BindBeforeLoadData = $this->getBindBeforeLoadData($relKey);
                if ($BindBeforeLoadData) {
                    // то немножечко подстраиваем формат их хранения
                    // только если не используются динамические названия моделей
                    if ($is_dynamical_model && !isset($BindBeforeLoadData[$model]))
                        $BindBeforeLoadData = array($model => $BindBeforeLoadData);
                }
                
                
                if ($relVal['type'] === 'has_one') {
                    
                    $hasOneData = array();
                    foreach($hasKeys[$relKey] as $model => $bindIds) {
                        
                        $oModel = \OrmManager::getModelInstance($model);
                        $oids = array_unique($bindIds);
                        
                        $hasOneData[$model] = array();
                        if (!empty($oids)) {
                            // Если ранее уже загружались данные прикрепленного бинда
                            if ($BindBeforeLoadData && isset($BindBeforeLoadData[$model])) {
                                foreach($BindBeforeLoadData[$model] as $rb) {
                                    if (false === ($mid_id = array_search($rb->{'get' . ucfirst($key_search)}(),$mids)))
                                        continue;
                                    $hasOneData[$model][] = $rb;
                                    unset($oids[$mid_id]);
                                }
                            }
                            if (!empty($oids)) {
                                $oids = implode(', ', $oids);
                                $where = array('`id` IN (' . $oids . ')');
                                if ($this->getBindParams($relKey)) {
                                    $where = array_merge($where, $this->getBindParams($relKey));
                                }
                                
                                $collection = $oModel->getCollection($where);
                                if (!empty($collection) && is_array($collection))
                                    $hasOneData[$model] = array_merge($collection, $hasOneData[$model]);
                            }
                        }
                    }
                    
                    // Assign entities
                    if (is_array($hasOneData) && count($hasOneData)) {
                        foreach ($hasOneData as $model => $bindData) {
                            foreach($bindData as $ok => $ov) {
                                foreach ($records as $rk => $rv) {
                                   if ($rv[$foreignKey] == $ov->getId() && (!$is_dynamical_model || $rv[$keyWithModel] == $model)) {
                                       $records[$rk][$relKey] = $ov;
                                       continue;
                                   }
                                }
                            }
                        }
                    }


                // and has_many...
                } else if ($relVal['type'] === 'has_many') {
                    
                    $hasManyData = array();
                    foreach($hasKeys[$relKey] as $model => $bindIds) {
                        
                        
                        $mModel = \OrmManager::getModelInstance($model);
                        
                        
                        // Если указан ключ из текущей таблицы
                        // То получить несколько значений можно только в том случае,
                        // Если в этом ключе перечислены идентификаторы прикрепленных к нему материалов
                        if ($is_this_table) {
                            $mids = array_unique($bindIds);
                            $key_search = "id";
                        } else {
                            $mids = array_unique($ids);
                            $key_search = $foreignKey;
                        }
                        
                        
                        // Get entities
                        $hasManyData[$model] = array();
                        if (!empty($mids)) {
                            // Если ранее уже загружались данные прикрепленного бинда
                            if ($BindBeforeLoadData && isset($BindBeforeLoadData[$model])) {
                                foreach($BindBeforeLoadData[$model] as $rb) {
                                    if (false === ($mid_id = array_search($rb->{'get' . ucfirst($key_search)}(),$mids)))
                                        continue;
                                    $hasManyData[$model][] = $rb;
                                    unset($mids[$mid_id]);
                                }
                            }
                            if (!empty($mids)) {
                                $mids = implode(', ', $mids);
                                $where = array('`' . $key_search . '` IN (' . $mids . ')');
                                if ($this->getBindParams($relKey)) {
                                    $where = array_merge($where, $this->getBindParams($relKey));
                                }
                                $collection = $mModel->getCollection($where);
                                if (!empty($collection) && is_array($collection))
                                    $hasManyData[$model] = array_merge($collection, $hasManyData[$model]);
                            }
                        }
                    }
                    
                    
                    
                    // Assign entities
                    if (!empty($hasManyData) && count($hasManyData)) {
                        if ($is_this_table) {
                            foreach ($hasManyData as $model => $bindData) {
                                foreach ($bindData as $mk => $mv) {
                                    foreach ($records as $rk => $rv) {
                                        if (!array_key_exists($relKey, $rv))
                                            $records[$rk][$relKey] = array();
                                        
                                        if (in_array($mv->getId(), explode(',', (string)($rv[$foreignKey])))
                                        && (!$is_dynamical_model || $rv[$keyWithModel] == $model) ) {
                                            foreach($records[$rk][$relKey] as $rb) {
                                                if ($rb->getId() == $mv->getId())
                                                    continue 2;
                                            }
                                            $records[$rk][$relKey][] = $mv;
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ($hasManyData as $model => $bindData) {
                                foreach ($bindData as $mk => $mv) {
                                    foreach ($records as $rk => $rv) {
                                        if (!array_key_exists($relKey, $records[$rk]))
                                            $records[$rk][$relKey] = array();
                                       
                                        if ($rv['id'] == $mv->{'get' . ucfirst($foreignKey)}()
                                        && (!$is_dynamical_model || $rv[$keyWithModel] == $model)) {
                                            $records[$rk][$relKey][] = $mv;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        foreach ($records as $rk => $rv) {
                            $records[$rk][$relKey] = array();
                        }
                    }
                }
            }
        }
        return $records;
    }



    public function bindModel($modelName, $before_load_data = array(), $params = array())
    {
        if (empty($this->RelatedEntities) || !is_array($this->RelatedEntities)) return false;

        foreach ($this->RelatedEntities as $relKey => $relEntity) {
            if ($relKey === $modelName) {
                $this->setBindParams($modelName, array($before_load_data,$params));
                return true;
            }
        }
        return false;
    }



    public function getBindParams($modelName = false)
    {
        if (!$modelName) return $this->BindedParams;
        return ($this->BindedParams[$modelName]) ? $this->BindedParams[$modelName][1] : false;
    }


    public function getBindBeforeLoadData($modelName = false)
    {
        return ($this->BindedParams[$modelName]) ? $this->BindedParams[$modelName][0] : false;
    }



    public function setBindParams($modelName, $params = array())
    {
        $this->BindedParams[$modelName] = $params;
    }
    
    public function isBindModel($modelName)
    {
        return array_key_exists($modelName, $this->BindedParams);
    }


    /**
     * @param array $params
     * @param array $addParams
     * @return array|bool
     */
    public function getCollection($params = array(), $addParams = array())
    {
        $addParams['cond'] = $params;
        $entities = getDB()->select($this->Table, DB_ALL, $addParams);


        if (!empty($entities)) {
            $entities = $this->getAllAssigned($entities);
            $entityClassName = \OrmManager::getEntityNameFromModel(get_class($this));
            foreach ($entities as $key => $entity) {
                $entities[$key] = new $entityClassName($entity);
            }
            return (!empty($entities)) ? $entities : false;
        }

        return false;
    }




    /**
     * @param array $params
     * @param array $addParams
     * @return array|bool
     */
    public function getFirst($params = array(), $addParams = array())
    {
        $addParams['limit'] = 1;
        $entities = $this->getCollection($params, $addParams);
        return (!empty($entities) && is_array($entities) && count($entities) && isset($entities[0])) ? $entities[0] : false;
    }




    /**
     * @param $parentEntity
     * @param $varName
     * @return mixed
     */
    public function loadRelativeData($parentEntity, $varName)
    {

        $relParams = $this->getRelatedEntitiesParams();
        if (!count($relParams) || !array_key_exists($varName, $relParams)) return false;


        $relParams = $relParams[$varName];
        $ModelName = \OrmManager::getModelName($relParams['model']);
        $Model = new $ModelName($relParams['model']);


        switch ($relParams['type']) {
            case 'has_one':
                $methodName = 'get' . ucfirst($relParams['foreignKey']);
                $data = $Model->getById($parentEntity->$methodName());
                break;
            case 'has_many':
                $params = array(
                    $relParams['foreignKey'] => $parentEntity->getId(),
                );
                $data = $Model->getCollection($params);
                break;
        }
        return $data;
    }



    /**
     * @return array|bool
     */
    public function getRelatedEntitiesParams()
    {
        return (!empty($this->RelatedEntities)) ? $this->RelatedEntities : false;
    }



    public function getOneField($field, $params)
    {
        $output = array();
        $result = getDB()->select($this->Table, DB_ALL, array(
            'cond' => $params,
            'fields' => array($field),
        ));

        if (!empty($result)) {
            foreach($result as $key => $record) {
                $output[] = $record[$field];
            }
        }

        return $output;
    }



    public function deleteByParentId($id, $module = null)
    {
        $Register = Register::getInstance();
        $where = array(
            'entity_id' => $id,
        );
        if ($module) {
            $where['module'] = $module;
        }
        //$records = getDB()->select($this->Table, DB_ALL, array('cond' => $where));
        $records = $this->getCollection($where);


        if ($records) {
            foreach ($records as $k => $v) {
                $v->delete();
            }
        }
    }

}

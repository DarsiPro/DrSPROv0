<?php
/**
* @project    DarsiPro CMS
* @package    LoadsAttaches Entity
* @url        https://darsi.pro
*/


namespace LoadsModule\ORM;

class LoadsAttachesEntity extends \OrmEntity
{

    protected $id;
    protected $entity_id;
    protected $user_id;
    protected $attach_number;
    protected $filename ;
    protected $size;
    protected $date;
    protected $is_image;


    public function save()
    {
        $params = array(
            'entity_id' => intval($this->entity_id),
            'user_id' => intval($this->user_id),
            'attach_number' => intval($this->attach_number),
            'filename' => $this->filename,
            'size' => intval($this->size),
            'date' => $this->date,
            'is_image' => (!empty($this->is_image)) ? '1' : new \Expr("'0'"),
        );
        if($this->id) $params['id'] = $this->id;

        return (getDB()->save('loads_attaches', $params));
    }



    public function delete()
    {
        $path_files = ROOT . '/data/files/loads/' . $this->filename;
        $path_images = ROOT . '/data/images/loads/' . $this->filename;
        if (file_exists($path_files)) {
            unlink($path_files);
        } elseif (file_exists($path_images)) {
            unlink($path_images);
        }

        if (\Config::read('use_local_preview', 'loads')) {
            $preview = \Config::read('use_preview', 'loads');
            $size_x = \Config::read('img_size_x', 'loads');
            $size_y = \Config::read('img_size_y', 'loads');
        } else {
            $preview = \Config::read('use_preview');
            $size_x = \Config::read('img_size_x');
            $size_y = \Config::read('img_size_y');
        }
        $path = ROOT.'/data/images/loads/'.$size_x.'x'.$size_y.'/'.$this->filename;
        if (file_exists($path)) unlink($path);


        getDB()->delete('loads_attaches', array('id' => $this->id));
    }

    public function __getAPI() {

        if (
            !\ACL::turnUser(array('loads', 'view_list')) ||
            !\ACL::turnUser(array('loads', 'view_materials')) ||
            !\ACL::turnUser(array('loads', 'download_files'))
        )
            return array();

        return array(
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'user_id' => $this->user_id,
            'attach_number' => $this->attach_number,
            'filename' => $this->filename,
            'size' => $this->size,
            'date' => $this->date,
            'is_image' => $this->is_image,
        );
    }
}
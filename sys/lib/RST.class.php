<?php
/**
 * @project     DarsiPro CMS
 * @package     RST class
 * @url         https://darsi.pro
 * Класс-обертка для обращения к переменным _GET, _POST, _FILES
 */
class RST {


    
    /**
	 * Конструктор, чистка слешей
	 */
	public function __construct()
	{		
		parent::__construct();
		
		$_POST = $this->stripslashes_recursive($_POST);
		$_GET = $this->stripslashes_recursive($_GET);	
	}
    
    
    
    
    
    
    
    
    
    
    
    /**
	* Определение request-метода обращения к странице (GET, POST)
	* Если задан аргумент функции (название метода, в любом регистре), возвращает true или false
	* Если аргумент не задан, возвращает имя метода
	* Пример:
	* 
	*	if($simpla->request->method('post'))
	*		print 'Request method is POST';
	* 
	*/
    public static function method($method = null)
    {
    	if(!empty($method))
    		return strtolower($_SERVER['REQUEST_METHOD']) == strtolower($method);
	    return $_SERVER['REQUEST_METHOD'];
    }

	/**
	* Возвращает переменную _GET, отфильтрованную по заданному типу, если во втором параметре указан тип фильтра
	* Второй параметр $type может иметь такие значения: integer, string, boolean
	* Если $type не задан, возвращает переменную в чистом виде
	*/
    public function get($name, $type = null)
    {
    	$val = null;
    	if(isset($_GET[$name]))
    		$val = $_GET[$name];
    		
    	if(!empty($type) && is_array($val))
    		$val = reset($val);
    	
    	if($type == 'string')
    		return strval(preg_replace('/[^\p{L}\p{Nd}\d\s_\-\.\%\s]/ui', '', $val));
    		
    	if($type == 'integer')
    		return intval($val);

    	if($type == 'boolean')
    		return !empty($val);
    		
    	return $val;
    }

	/**
	* Возвращает переменную _POST, отфильтрованную по заданному типу, если во втором параметре указан тип фильтра
	* Второй параметр $type может иметь такие значения: integer, string, boolean
	* Если $type не задан, возвращает переменную в чистом виде
	*/
    public function post($name = null, $type = null)
    {
    	$val = null;
    	if(!empty($name) && isset($_POST[$name]))
    		$val = $_POST[$name];
    	elseif(empty($name))
    		$val = file_get_contents('php://input');
    		
    	if($type == 'string')
    		return strval(preg_replace('/[^\p{L}\p{Nd}\d\s_\-\.\%\s]/ui', '', $val));
    		
    	if($type == 'integer')
    		return intval($val);

    	if($type == 'boolean')
    		return !empty($val);

    	return $val;
    }

	/**
	* Возвращает переменную _FILES
	* Обычно переменные _FILES являются двухмерными массивами, поэтому можно указать второй параметр,
	* например, чтобы получить имя загруженного файла: $filename = $simpla->request->files('myfile', 'name');
	*/
    public function files($name, $name2 = null)
    {
    	if(!empty($name2) && !empty($_FILES[$name][$name2]))
    		return $_FILES[$name][$name2];
    	elseif(empty($name2) && !empty($_FILES[$name]))
    		return $_FILES[$name];
    	else
    		return null;
    }

	/**
	 * Рекурсивная чистка магических слешей
	 */
	private function stripslashes_recursive($var)
	{
		if(get_magic_quotes_gpc())
		{
			$res = null;
			if(is_array($var))
				foreach($var as $k=>$v)
					$res[stripcslashes($k)] = $this->stripslashes_recursive($v);
				else
					$res = stripcslashes($var);
		}
		else
		{
			$res = $var;
		}
		return $res;
	}
    
    	
	/**
	* Проверка сессии
	*/
    public function check_session()
    {
		if(!empty($_POST))
		{
			if(empty($_POST['session_id']) || $_POST['session_id'] != session_id())
			{
				unset($_POST);
				return false;
			}
		}
		return true;
    }
    
    
    
    
    
    
}


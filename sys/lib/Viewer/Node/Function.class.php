<?php



class Viewer_Node_Function
{

    private $func;
    private $params;
    
    static $AllowFunctions;
    
    /**
     * if @var == 1 variable should be $this->getValue(context, value)()
     * @var bool
     */
    private $def = true;


    public function __construct($func, $params = array())
    {   
        $this->def = function_exists($func);
        
        if ($this->def) {
            if (empty(self::$AllowFunctions))
                self::$AllowFunctions = include(R.'sys/lib/Viewer/AllowFnc.php');
            if (!in_array($func,self::$AllowFunctions))
                throw new Exception("Function $func is not available for use in the Viewer");
        }
        
        $this->func = $func;
        $this->params = $params;
    }


    public function addParam($node) {
        array_push($this->params, $node);
    }


    public function compile(Viewer_CompileParser $compiler)
    {   
        
        
        $value = $this->compileValue($compiler);
        $compiler->raw($value);
        while (count($this->params) > 0) {
            $node = array_shift($this->params);
            $compiler->raw($node->compile($compiler));
            if (count($this->params) > 0) $compiler->raw(", ");
        }
        $compiler->raw(")");
    }
    
    
    private function compileValue(Viewer_CompileParser $compiler)
    {
        if (!$this->def) {
            $value = "\$this->getValue(\$this->context, '{$this->func}', true)";
            
            $value = "call_user_func(".$value;
            if (count($this->params) > 0)
                $value .= ", ";

        } else {
            $value = "$this->func(";
        }
        return $value;
    }


    public function __toString()
    {
        $out = '[function_name]:' . $this->func . "\n";
        $out .= '[params]:' . implode("<br>\n", $this->params) . "\n";
        return $out;
    }
}
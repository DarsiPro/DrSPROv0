<?php


class Viewer_CompileParser
{

    protected $nodes;
    private $output;
    private $indent = 3;
    private $tmpClassName = 3;
    public $loader;



    public function __construct(Viewer_Loader $loader)
    {
        $this->loader = $loader;
    }



    public function clean()
    {
        $this->output = null;
    }



    public function getOutput()
    {
        return $this->output;
    }



    protected function getTmpClassName()
    {
        return $this->tmpClassName;
    }



    public function setTmpClassName($className)
    {
        $this->tmpClassName = $className;
    }



    public function write()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            $this->addIndent();
            $this->output .= $arg;
        }

        return $this;
    }




    public function indent($step = 1)
    {
        $this->indent += $step;

        return $this;
    }




    public function outdent($step = 1)
    {
        $this->indent -= $step;

        if ($this->indent < 0) {
            throw new Twig_Error('Unable to call outdent() as the indentation would become negative');
        }

        return $this;
    }




    public function string($value, $quoted = true)
    {
        if ($quoted) {
            $value = str_replace(array("\n\t", "\t"), array("\n", ""), $value);
            $this->output .= sprintf('"%s"', addcslashes($value, "\0\t\"\$\\"));
        } else {
            $this->output .= sprintf('"%s"', $value);
        }

        return $this;
    }


    public function constant($value) {

        $this->output .= sprintf('%s', strtoupper($value));

        return $this;
    }

    public function raw($string)
    {
        $this->output .= $string;

        return $this;
    }




    public function repr($value)
    {
        if (is_int($value) || is_float($value)) {
            $this->raw($value);
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (is_array($value)) {
            $this->raw('array(');
            $i = 0;
            foreach ($value as $key => $val) {
                if ($i++) {
                    $this->raw(', ');
                }
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($val);
            }
            $this->raw(')');
        } elseif (defined($value)) {
            $this->constant($value);
        } else {
            $this->string($value);
        }

        return $this;
    }




    public function addIndent()
    {
        $this->output .= str_repeat(' ', $this->indent * 4);
    }




    public function subcompile($node)
    {
        $node->compile($this);
        return $this;
    }




    public function compile(Viewer_NodeTree $nodes)
    {
        $this->nodes = $nodes;
        $nodesBody = $nodes->getBody();
        if (is_array($nodesBody) && count($nodesBody) > 0) {
            foreach ($nodesBody as $key => $node) {
                $node->compile($this);
            }
        }


        $this->finishSourseCode();


        return $this;
    }



    private function finishSourseCode()
    {
        $class = $this->getTmpClassName();

        $str = '<?php' . "\n";
        $str .= 'if (!class_exists("' . $class . '")) {' . "\n";
        $str .= '    class ' . $class . ' extends Viewer_Template {' . "\n";
        $str .= '        public function display() {' . "\n";
        $str .= $this->output;
        $str .= '        }' . "\n";
        $str .= '    }' . "\n";
        $str .= '}' . "\n";
        $str .= "\${$class} = new {$class}(\$context);" . "\n";
        $str .= "\${$class}->display();" . "\n";
        $this->output = $str;
    }
}
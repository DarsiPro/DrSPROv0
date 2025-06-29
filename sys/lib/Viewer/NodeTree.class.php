<?php


class Viewer_NodeTree
{
    protected $body;


    public function __construct($body)
    {
        $this->body = $body;
    }




    public function getBody()
    {
        return $this->body;
    }




    public function __toString()
    {
        $out = 'body(' . "\n";
        if (!empty($this->body)) {
            foreach ($this->body as $key => $node) {
                $out .= get_class($node) . ':' . $node . "\n";
            }
        }

        return $out;
    }


}
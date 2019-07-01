<?php

class ComPagesTemplateHelperSnippet extends ComPagesTemplateHelperAbstract
{
    private $__snippets = array();

    public function define(string $name, string $snippet, $overwrite = false)
    {
        if(!isset($this->__snippets[$name]) || $overwrite) {
            $this->__snippets[$name] = $snippet;
        }
    }

    public function expand(string $name, array $variables = array())
    {
        $result = false;

        if(isset($this->__snippets[$name]))
        {
            $snippet = $this->__snippets[$name];

            //Use the stream buffer to evaluate the partial
            $str = "<?php \n echo <<<SNIPPET\n$snippet\nSNIPPET;\n";

            $result = $this->getObject('template.engine.factory')
                ->createEngine('php')
                ->loadString($str)
                ->render($variables);

            //Cleanup whitespace
            //$result = str_replace(array(' >', ' "'), array('>', '"'), $result);
        }

        return $result;
    }
}


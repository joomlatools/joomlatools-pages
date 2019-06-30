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
            $str = "<?php \n return <<<SNIPPET\n$snippet\nSNIPPET;\n";

            $buffer = $this->getObject('filesystem.stream.factory')->createStream('koowa-buffer://temp', 'w+b');
            $buffer->truncate(0);
            $buffer->write($str);

            extract($variables, EXTR_OVERWRITE);

            $result = include $buffer->getPath();
        }

        return $result;
    }
}


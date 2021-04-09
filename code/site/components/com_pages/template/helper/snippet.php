<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateHelperSnippet extends ComPagesTemplateHelperAbstract
{
    private $__snippets = array();

    public function __invoke(string $name, $snippet = null)
    {
        if(!is_null($snippet))
        {
            if(is_string($snippet)) {
                $result = $this->define($name, $snippet);
            } else {
                $result = $this->expand($name, $snippet);
            }
        }
        else $result = $this->exists($name);

        return $result;
    }

    public function define(string $name, string $snippet, $overwrite = false)
    {
        $result = false;

        if(!isset($this->__snippets[$name]) || $overwrite)
        {
            $this->__snippets[$name] = $snippet;
            $result = true;
        }

        return $result;
    }

    public function expand(string $name, array $variables = array())
    {
        $result = false;

        if($this->exists($name))
        {
            $snippet = $this->__snippets[$name];

            //Use the php template engine to evaluate
            $str = "<?php \n echo <<<SNIPPET\n$snippet\nSNIPPET;\n";

            $result = $this->getObject('template.engine.factory')
                ->createEngine('php')
                ->loadString($str)
                ->render($variables);

            //Find single whitespace before " or before > in html tags and remove it
            preg_match_all('#<\s*\w.*?>#', $result, $tags);

            foreach($tags as $tag) {
                $result = str_replace($tag,  str_replace(array(' >', ' "'), array('>', '"'), $tag), $result);
            }
        }
        else throw new RuntimeException('Snippet: '.$name.' does not exist');

        return $result;
    }

    public function exists(string $name)
    {
        $result = false;

        if(isset($this->__snippets[$name])) {
            $result = true;
        }

        return $result;
    }
}


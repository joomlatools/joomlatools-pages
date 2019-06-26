<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

abstract class ComPagesTemplateHelperAbstract extends KTemplateHelperAbstract
{
    public function evaluateString($string, array $variables = array())
    {
        //Use the stream buffer to evaluate the partial
        $str = "<?php \n return <<<STRING\n$string\nSTRING;\n";

        $buffer = $this->getObject('filesystem.stream.factory')->createStream('koowa-buffer://temp', 'w+b');
        $buffer->truncate(0);
        $buffer->write($str);

        extract($variables, EXTR_OVERWRITE);

        return include $buffer->getPath();
    }
}
<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


/**
 * Block Template Filter
 *
 * Filter will parse elements of the form <ktml:block name="[name]" if=[condition]" /> as named blocks
 * and elements of the form <ktml:block (extend|prepend|replace)="[name]">[content]</ktml:block> to be
 * injected into the named block.
 *
 * By default blocks will be appended, they can also be prepended [prepend] or replaced [replace] the named
 * block.
 *
 * The block will not be rendered if there are no blocks extending it, an optional if attribute can be
 * provided to define a more advanced condition as to when the block should be rendered. Only if the
 * condition evaluates to TRUE the block will be rendered.
 *
 * Example <ktml:block name="sidebar" if="sidebar > 2"> In this case the sidebar will be rendered only
 * if at least two blocks have been injected.
 */
class ComPagesTemplateFilterBlock extends ComPagesTemplateFilterAbstract
{
    private $__blocks;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOW,
        ));

        parent::_initialize($config);
    }

    public function filter(&$text)
    {
        $this->_parseTags($text);
    }

    public function addBlock($name, array $data)
    {
        if(!isset($this->__blocks[$name])) {
            $this->__blocks[$name] = array();
        }

        $this->__blocks[$name][] = $data;

        return $this;
    }

    public function getBlocks($name)
    {
        $result = array();

        if(isset($this->__blocks[$name])) {
            $result = $this->__blocks[$name];
        }

        return $result;
    }

    public function hasBlocks($name)
    {
        return isset($this->__blocks[$name]) && !empty($this->__blocks[$name]);
    }

    public function clearBlocks($name)
    {
        if($this->hasBlocks($name)) {
            unset($this->__blocks[$name]);
        }

        return $this;
    }

    protected function _parseTags(&$text)
    {
        $replace = array();
        $matches = array();

        // <ktml:block extend|prepend|replace="[name]"></khtml:block>
        if(preg_match_all('#<ktml:block\s+(extend|prepend|replace)="([^"]+)"(.*)>(.*)</ktml:block>#siU', $text, $matches))
        {
            $count = count($matches[0]);

            for($i = 0; $i < $count; $i++)
            {
                $name = $matches[2][$i];

                //Create attributes array
                $defaults = array(
                    'title'   => '',
                    'extend'  => ''
                );

                $attributes = array_merge($defaults, $this->parseAttributes($matches[3][$i]));
                $content    = trim($matches[4][$i]);

                //Skip empty modules
                if (!empty($content))
                {
                    //Create block
                    $block = array(
                        'content'  => $matches[4][$i],
                        'title'    => $attributes['title'],
                        'extend'   => $matches[1][$i],
                        'attribs'  => (array) array_diff_key($attributes, $defaults)
                    );

                    //Clear any prior added blocks
                    if($block['extend'] == 'replace') {
                        $this->clearBlocks($name);
                    }

                    $this->addBlock($name, $block);

                    //Do not continue adding blocks
                    if($block['extend'] == 'replace') {
                        break;
                    }
                }
            }

            //Remove the tags
            $text = str_replace($matches[0], '', $text);
        }

        // <ktml:block name="[name]" if="[condition]"></ktml:block>
        if(preg_match_all('#<ktml:block\s+name="([^"]+)"(.*"[^"]*")?>(.*)</ktml:block>#siU', $text, $matches))
        {
            $count = count($matches[1]);

            for($i = 0; $i < $count; $i++)
            {
                $name = $matches[1][$i];
                $replace[$i] = '';

                if($this->isEnabled() && $this->hasBlocks($name))
                {
                    $attribs = $this->parseAttributes( $matches[2][$i] );

                    if(isset($attribs['if']))
                    {
                        if($this->_countBlocks($attribs['if']))
                        {
                            unset($attribs['if']);
                            $replace[$i] = $this->_renderBlocks($name, $attribs);
                        }
                    }
                    else $replace[$i] = $this->_renderBlocks($name, $attribs);

                    if(!empty($replace[$i])) {
                        $replace[$i] = str_replace('<ktml:block>', $replace[$i], $matches[3][$i]);
                    }
                }
            }

            $text = str_replace($matches[0], $replace, $text);
        }

        $replace = array();
        $matches = array();
        // <ktml:block name="[name]" if="[condition]" />
        if(preg_match_all('#<ktml:block\s+name="([^"]+)"(.*"[^"]*")?\s+\/>#siU', $text, $matches))
        {
            $count = count($matches[1]);

            for($i = 0; $i < $count; $i++)
            {
                $name = $matches[1][$i];
                $replace[$i] = '';

                if($this->isEnabled() && $this->hasBlocks($name))
                {
                    $attribs = $this->parseAttributes( $matches[2][$i]);

                    if(isset($attribs['if']))
                    {
                        if($this->_countBlocks($attribs['if']))
                        {
                            unset($attribs['if']);
                            $replace[$i] = $this->_renderBlocks($name, $attribs);
                        }
                    }
                    else $replace[$i] = $this->_renderBlocks($name, $attribs);

                }
            }

            $text = str_replace($matches[0], $replace, $text);
        }
    }

    protected function _renderBlocks($name, $attribs = array())
    {
        $html   = '';
        $count  = 1;
        $blocks = $this->getBlocks($name);

        foreach($blocks as $block)
        {
            //Set the block attributes
            if($count == 1) {
                $attribs['rel']['first'] = 'first';
            }

            if($count == count($blocks)) {
                $attribs['rel']['last'] = 'last';
            }

            if(isset($block['attribs'])) {
                $block['attribs'] = array_merge((array) $block['attribs'], $attribs);
            } else {
                $block['attribs'] = $attribs;
            }

            //Render the block
            $content = $this->_renderBlock($block);

            //Prepend or append the block
            if($block['extend'] == 'prepend') {
                $html = $content.$html;
            } else {
                $html = $html.$content;
            }

            $count++;
        }

        return $html;
    }

    protected function _renderBlock($block)
    {
        $result = '';

        if(isset($block['content'])) {
            $result = $block['content'];
        }

        return $result;
    }

    protected function _countBlocks($condition)
    {
        $operators = '(\+|\-|\*|\/|==|\!=|\<\>|\<|\>|\<=|\>=|and|or|xor)';
        $words = preg_split('# ' . $operators . ' #', $condition, null, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0, $n = count($words); $i < $n; $i += 2)
        {
            // Odd parts (blocks)
            $name = strtolower($words[$i]);

            if(!is_numeric($name)) {
                $words[$i] = count($this->getBlocks($name));
            } else {
                $words[$i] = $name;
            }
        }

        //Use the stream buffer to evaluate the condition
        $str = '<?php return ' . implode(' ', $words) .';';

        $buffer = $this->getObject('filesystem.stream.factory')->createStream('koowa-buffer://temp', 'w+b');
        $buffer->truncate(0);
        $buffer->write($str);

        $result = include $buffer->getPath();

        return $result;
    }
}
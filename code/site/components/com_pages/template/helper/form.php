<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateHelperForm extends ComPagesTemplateHelperAbstract
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->helper('snippet.define', 'form.honeypot',
            '<div style="display:none !important;">
                <input type="text" name="$name" value="" autocomplete="false" tabindex="-1">
            </div>'
        );
    }

    public function honeypot()
    {
        $config = new ComPagesObjectConfig();
        $config->append(array(
            'name'    => $this->getTemplate()->page()->form->honeypot,
            'snippet' => 'form.honeypot',
        ));

        return $this->helper('snippet.expand', $config->snippet, $config->toArray());
    }
}
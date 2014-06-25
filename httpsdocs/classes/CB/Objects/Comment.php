<?php
namespace CB\Objects;

use CB\Config;
use CB\Util;

class Comment extends Object
{

    /**
     * internal function used by create method for creating custom data
     * @return void
     */
    public function create($p = false)
    {
        if ($p === false) {
            $p = &$this->data;
        }

        if (!empty($p['data']['_title'])) {
            $msg = Util\adjustTextForDisplay($p['data']['_title']);
            $msg = $this->processAndFormatMessage($msg);
            $p['name'] = $msg;
            $p['data']['_title'] = $msg;
        }

        parent::create($p);
    }

    /**
     * process a message:
     *     - replace urls with links
     *     - replace object references with links
     * @param varchar $message
     */
    protected function processAndFormatMessage($message)
    {
        // replace urls with links
        $message = Util\replaceUrlsWithLinks($message);

        //replace object references with links
        if (preg_match_all('/#(\d+)[^#\d]*/', $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = \CB\Objects::getName($match[1]);
                $name = (strlen($name) > 30)
                    ? substr($name, 0, 30).'&hellip;'
                    : $name;
                $message = str_replace(
                    $match[0],
                    '<a href="' . Config::get('core_url') . 'v-' . $match[1] . '" target="_blank">' . $name . '</a>' . substr($match[0], strlen($match[1]) + 1),
                    $message
                );
            }
        }

        return $message;
    }
}
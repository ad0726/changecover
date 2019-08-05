<?php

use \Symfony\Component\HttpFoundation\Response;
use \ady\changecover\core;

namespace ady\changecover\controller;


class main
{
    /* @var \phpbb\config\config */
    protected $config;

    /* @var \phpbb\controller\helper */
    protected $helper;

    /* @var \phpbb\template\template */
    protected $template;

    /* @var \phpbb\user */
    protected $user;

    /* @var \phpbb\request */
    protected $request;

    /**
     * Constructor
     *
     * @param \phpbb\config\config      $config
     * @param \phpbb\controller\helper  $helper
     * @param \phpbb\template\template  $template
     * @param \phpbb\user               $user
     * @param \phpbb\request\request_interface
     */
    public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request_interface $request)
    {
        $this->config   = $config;
        $this->helper   = $helper;
        $this->template = $template;
        $this->user     = $user;
        $this->request  = $request;
    }

    /**
     * Demo controller for route /demo/{name}
     *
     * @param string $name
     * @throws \phpbb\exception\http_exception
     * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
     */
    public function handle($path)
    {
        $submit = $this->request->is_set_post('post');

        if (!$submit) {
            if ($path === 'requestcover') {
                return $this->helper->render('requestcover.html');
            } elseif ($path === 'validecover') {
                return $this->helper->render('validecover.html');
            } else {
                throw new \phpbb\exception\http_exception(403, 'NO_AUTH_SPEAKING', array($path));
            }
        } else {
            $url     = $this->request->variable('url'    , '', true);
            $section = $this->request->variable('section', '', true);
            $file    = $this->request->file('cover');

            $upload = \ady\changecover\core\functions::uploadCover($file);
            d($upload);
        }

    }
}

function d($data) {
	die(print_r($data, 1));
}
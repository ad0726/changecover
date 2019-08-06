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

	/** \phpbb\auth */
	protected $auth;

	/** \phpbb\db\driver\driver_interface */
    protected $db;

	/** @var string table_prefix */
	protected $table_prefix;

    /**
     * Constructor
     *
     * @param \phpbb\config\config              $config
     * @param \phpbb\controller\helper          $helper
     * @param \phpbb\template\template          $template
     * @param \phpbb\user                       $user
	 * @param \phpbb\auth\auth		            $auth
     * @param \phpbb\db\driver\driver_interface $db
     * @param \phpbb\request\request_interface  $request
	 * @param string                            $table_prefix
     *
     */
    public function __construct(
        \phpbb\config\config $config,
        \phpbb\controller\helper $helper,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\auth\auth $auth,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\request\request_interface $request,
        $table_prefix)
    {
        $this->config       = $config;
        $this->helper       = $helper;
        $this->template     = $template;
        $this->user         = $user;
        $this->auth         = $auth;
        $this->db           = $db;
        $this->request      = $request;
        $this->table_prefix = $table_prefix;
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
            $urlRelease = $this->request->variable('url'    , '', true);
            $section    = $this->request->variable('section', '', true);
            $file       = $this->request->file('cover');

            $upload = \ady\changecover\core\functions::uploadCover($file);
            if ($upload[0]) {
                $pathCover = $upload[1];

                $dataToDB = [
                    "section"     => $section,
                    "url_release" => $urlRelease,
                    "path_cover"  => $pathCover,
                    "user_id"     => $this->user->data['user_id']
                ];

                $sql = 'INSERT INTO ' . $this->table_prefix . 'changecover_tobeapproved ' . $this->db->sql_build_array('INSERT', $dataToDB);

                if (!$this->db->sql_query($sql)) {
                    return $this->helper->render('error.html');
                } else {
                    return $this->helper->render('request_success.html');
                }
            } else {
                return $this->helper->render('error.html');
            }
        }

    }
}

function d($data) {
	die(print_r($data, 1));
}
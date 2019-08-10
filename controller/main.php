<?php

namespace ady\changecover\controller;

use \Symfony\Component\HttpFoundation\Response;

class main
{
    /* @var \ady\changecover\core\functions */
    protected $ady_functions;

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
     * @param \ady\changecover\core\functions   $ady_functions
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
        \ady\changecover\core\functions     $ady_functions,
        \phpbb\config\config                $config,
        \phpbb\controller\helper            $helper,
        \phpbb\template\template            $template,
        \phpbb\user                         $user,
        \phpbb\auth\auth                    $auth,
        \phpbb\db\driver\driver_interface   $db,
        \phpbb\request\request_interface    $request,
                                            $table_prefix)
    {
        $this->ady_functions = $ady_functions;
        $this->config        = $config;
        $this->helper        = $helper;
        $this->template      = $template;
        $this->user          = $user;
        $this->auth          = $auth;
        $this->db            = $db;
        $this->request       = $request;
        $this->table_prefix  = $table_prefix;
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
            if ($path === 'request') {
                if (!$this->auth->acl_get('u_changecover_requester')) {
                    if (!$this->user->data['is_registered']){
                        login_box();
                    }

                    throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
                }

                return $this->helper->render('request.html');
            } elseif ($path === 'approve') {
                if (!$this->auth->acl_get('u_changecover_approver')) {
                    if (!$this->user->data['is_registered']){
                        login_box();
                    }

                    throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
                }

                $request = $this->ady_functions->fetchCoverToApprove();

                // Output the page
                $this->template->assign_vars([
                    "ALL_REQUEST" => $request
                ]);

                return $this->helper->render('approve.html');
            } else {
                throw new \phpbb\exception\http_exception(404, 'PAGE_NOT_FOUND', array($path));
            }
        } else {
            if ($path === 'request') {
                $urlRelease = $this->request->variable('url'    , '', true);
                $section    = $this->request->variable('section', '', true);
                $file       = $this->request->file('cover');

                $upload     = $this->ady_functions->uploadCover($file);
                if ($upload[0]) {
                    $pathCover = $upload[1];
                    $dataToDB  = [
                        "section"     => $section,
                        "url_release" => $urlRelease,
                        "path_cover"  => $pathCover,
                        "user_id"     => $this->user->data['user_id']
                    ];

                    $sql = 'INSERT INTO ' . $this->table_prefix . 'changecover_toapprove ' . $this->db->sql_build_array('INSERT', $dataToDB);

                    if (!$this->db->sql_query($sql)) {
                        return $this->helper->render('error.html');
                    } else {
                        // Output the page
                        $this->template->assign_vars([
                            "SUCCESS" => "sended"
                        ]);
                        return $this->helper->render('success.html');
                    }
                } else {
                    return $this->helper->render('error.html');
                }
            } elseif ($path === 'approve') {
                $approved = $this->request->variable('approved', [''=>''], true);

                $covers = $this->ady_functions->fetchAndParseForTabNews($approved);
                $update = $this->ady_functions->updateTabNews($covers);

                if (!$update) {
                    return $this->helper->render('error.html');
                } else {
                    $delete = $this->ady_functions->deleteRequest($approved);

                    if (!$delete) {
                        return $this->helper->render('error.html');
                    } else {
                        // Output the page
                        $this->template->assign_vars([
                            "SUCCESS" => "approved"
                        ]);
                        return $this->helper->render('success.html');
                    }
                }
            }
        }

    }
}

function d($data) {
	die(print_r($data, 1));
}
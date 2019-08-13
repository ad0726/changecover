<?php

namespace ady\changecover\controller;

use \Symfony\Component\HttpFoundation\Response;

class main
{
    /* @var \ady\changecover\core\functions */
    protected $ady_functions;

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
        \phpbb\controller\helper            $helper,
        \phpbb\template\template            $template,
        \phpbb\user                         $user,
        \phpbb\auth\auth                    $auth,
        \phpbb\db\driver\driver_interface   $db,
        \phpbb\request\request_interface    $request,
                                            $table_prefix)
    {
        $this->ady_functions = $ady_functions;
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

                return $this->helper->render('request.html', "Header Requester");
            } elseif ($path === 'approve') {
                if (!$this->auth->acl_get('u_changecover_approver')) {
                    if (!$this->user->data['is_registered']) {
                        login_box();
                    }

                    throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
                }

                $request = $this->ady_functions->fetchCoverToApprove();

                // Output the page
                $this->template->assign_vars([
                    "ALL_REQUEST" => $request
                ]);

                return $this->helper->render('approve.html', "Header Approver");
            } elseif ($path === 'home') {
                if (!$this->auth->acl_get('u_changecover_approver') &&
                    !$this->auth->acl_get('u_changecover_requester')) {
                    if (!$this->user->data['is_registered']) {
                        login_box();
                    }

                    throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
                }

                if ($this->auth->acl_get('u_changecover_approver')) {
                    // Output the page
                    $this->template->assign_vars([
                        "U_CHANGECOVER_APPROVER" => true
                    ]);
                }
                return $this->helper->render('home.html', "Header Home");
            } else {
                throw new \phpbb\exception\http_exception(404, 'PAGE_NOT_FOUND', [$path]);
            }
        } else {
            if ($path === 'request') {
                $error      = 0;
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
                        $error = 2;
                        // Output the page
                        $this->template->assign_vars([
                            "ERROR" => $error
                        ]);
                        return $this->helper->render('error.html', "Header Error");
                    } else {
                        // Output the page
                        $this->template->assign_vars([
                            "SUCCESS" => "sended"
                        ]);
                        return $this->helper->render('success.html', "Header Success");
                    }

                } else {
                    $error = 1;
                    // Output the page
                    $this->template->assign_vars([
                        "ERROR" => $error
                    ]);
                    return $this->helper->render('error.html', "Header Error");
                }

            } elseif ($path === 'approve') {
                $error  = 0;
                $radio  = $this->request->variable('radio', [''=>''], true);
                $submit = [
                    "approve" => [],
                    "remove"  => []
                ];

                foreach ($radio as $id=>$action) {
                    if ($action == "approve") {
                        $submit["approve"][] = $id;
                    } else if ($action == "remove") {
                        $submit["remove"][] = $id;
                    }
                }

                if (!empty($submit["remove"])) {
                    $files = [];
                    foreach ($submit["remove"] as $id) {
                        $cover   = $this->ady_functions->fetchCoverApproved($id);
                        $files[] = $cover['path_cover'];
                    }

                    $removed = $this->ady_functions->deleteRequest($submit["remove"]);

                    if (!$removed) {
                        $error += 1;
                    } else {
                        $this->ady_functions->removeFiles($files);
                    }
                }

                if (!empty($submit["approve"])) {
                    $covers  = $this->ady_functions->fetchAndParseForTabNews($submit["approve"]);
                    $updated = $this->ady_functions->updateTabNews($covers);

                    if (!$updated) $error += 2;
                }

                if ($error >= 2) {
                    // Output the page
                    $this->template->assign_vars([
                        "ERROR" => $error
                    ]);

                    return $this->helper->render('error.html', "Header Error");
                } else {
                    $delete = $this->ady_functions->deleteRequest($submit["approve"]);

                    if (!$delete) $error += 4;

                    // Output the page
                    $this->template->assign_vars([
                        "SUCCESS" => "approved",
                        "ERROR"   => $error
                    ]);

                    return $this->helper->render('success.html', "Header Success");
                }
            }
        }
    }

    /**
	 * Adds the template variables for the header link
	 */
	public function render_page_header_link()
	{
        $approver  = $this->auth->acl_get('u_changecover_approver');
        $requester = $this->auth->acl_get('u_changecover_requester');
		if (!$approver && !$requester) {
			return;
        }

        $countCoverToApprove = $this->ady_functions->countCoverToApprove();

		$template_data = [
			'U_CHANGECOVER_APPROVER'  => $approver,
			'U_CHANGECOVER_REQUESTER' => $requester,
			'NOTIFICATIONS'           => $countCoverToApprove
		];

        $this->template->assign_vars($template_data);
	}
}

// Function debug for dev
// function d($data) {
// 	die(print_r($data, 1));
// }

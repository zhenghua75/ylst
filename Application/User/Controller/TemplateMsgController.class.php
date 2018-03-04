<?php
namespace User\Controller;
use Common\Controller\UserController;
class TemplateMsgController extends UserController{

	public function __construct(){
		parent::__construct();
	}
	public function index(){

			$model = new \Extend\templateNews();
			$templs = $model->templates();

			$list = M('Tempmsg')->where(array('token'=>session('token')))->order('id DESC')->select();
			foreach ($list as $temp) {
				if (isset($templs[$temp['tempkey']])) {
					unset($templs[$temp['tempkey']]);
				}
				if (empty($temp['tempid']) && $temp['topcolor'] == '#ffffff' && $temp['textcolor'] == '#ffffff') {
					M('Tempmsg')->where(array('id' => $temp['id']))->save(array('topcolor' => '#029700', 'textcolor' => '#000000'));
				}
			}
			if ($templs) {
				foreach ($templs as $key => $val) {
					unset($val['vars']);
					$val['status'] = 0;
					$val['type'] = 0;
					$val['token'] = session('token');
					$val['topcolor'] = '#029700';
					$val['textcolor'] = '#000000';
					$val['tempkey'] = $key;
					$val['tempid'] = '';
					M('Tempmsg')->add($val);
				}
				$list = M('Tempmsg')->where(array('token'=>session('token')))->select();
			}
			$this->assign('list',$list);
			$this->display();
	}

	public function add()
	{
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$tempmsg = D('Tempmsg')->where(array('id' => $id, 'token' => $this->token))->find();
		$this->assign('tempmsg', $tempmsg);
		if (IS_POST) {
			$data['tempkey'] = isset($_POST['tempkey']) ? htmlspecialchars($_POST['tempkey']) : '';
			$data['tempid'] = isset($_POST['tempid']) ? htmlspecialchars($_POST['tempid']) : '';
			$data['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			$data['content'] = isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '';
			$data['industry'] = isset($_POST['industry']) ? htmlspecialchars($_POST['industry']) : '';
			$data['topcolor'] = isset($_POST['topcolor']) ? htmlspecialchars($_POST['topcolor']) : '#029700';
			$data['textcolor'] = isset($_POST['textcolor']) ? htmlspecialchars($_POST['textcolor']) : '#000000';
			$data['status'] = isset($_POST['status']) ? intval($_POST['status']) : 0;
			if (empty($data['tempkey'])) {
				$this->error('模板编号不能为空');
			} else {
				$check_tempmsg = D('Tempmsg')->where(array('tempkey' => $data['tempkey'], 'token' => $this->token))->find();
				if ($check_tempmsg && $id != $check_tempmsg['id']) {
					$this->error('模板编号已经存在');
				}
			}
			if (empty($data['tempid']) && $data['status'] == 1) {
				$this->error('模板ID不能为空');
			} else {
				$check_tempmsg = D('Tempmsg')->where(array('tempid' => $data['tempid'], 'token' => $this->token))->find();
				
				if ($data['tempid'] && $check_tempmsg && $id != $check_tempmsg['id']) {
					$this->error('模板ID已经存在');
				}
			}
			if (empty($data['content'])) {
				$this->error('回复内容不能为空');
			}
			if ($tempmsg) {
				D('Tempmsg')->where(array('id' => $id, 'token' => $this->token))->save($data);
				$this->success('修改模板成功', U('TemplateMsg/index'));
			} else {
				$data['token'] = $this->token;
				$data['type'] = 1;
				if (D('Tempmsg')->add($data)) {
					$this->success('新增模板成功', U('TemplateMsg/index'));
				} else {
					$this->error('新增模板失败');
				}
			}
		} else {
			$this->display();
		}
	}

	public function del()
	{
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if (D('Tempmsg')->where(array('id' => $id, 'token' => $this->token))->delete()) {
			$this->success('删除成功', U('TemplateMsg/index'));
		} else {
			$this->error('删除失败', U('TemplateMsg/index'));
		}
	}
}
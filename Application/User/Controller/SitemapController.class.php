<?php
namespace User\Controller;
use Common\Controller\BaseController;
class SitemapController extends BaseController{

	public function index(){
		header("Content-type:text/xml");
		echo $this->site();
	}


	public function site(){
		$preTpl='<?xml version="1.0" encoding="utf-8"?>'."\r\n".'<urlset>'."\r\n";
		$endTpl='</urlset>';
		if(I('get.c') != NULL ){
			$changefreq=I('get.c');
		}else{
			$changefreq='daily';
		}
		$token = I('get.token');
		
		$data=M('Img')->where(array('token'=>$token))->field('id,text,info,uptatetime,classid,token')->order("id DESC")->limit(10)->select();
		foreach($data as $k=>$v){
			$data[$k]['loc'] = C('site_url').'/index.php?g=Wap&amp;m=Index&amp;a=content&amp;id='.$v['id'].'&amp;classid='.$v['classid'].'&amp;token='.$v['token'];
			$tpl.='<url>'."\r\n".'<loc>'.$data[$k]['loc'].'</loc>'."\r\n".'<lastmod>'.$v['uptatetime'].'</lastmod>'."\r\n".'<changefreq>'.$changefreq.'</changefreq>'."\r\n".'</url>'."\r\n";
		}
		return $preTpl.$tpl.$endTpl;
	
	}
	


}
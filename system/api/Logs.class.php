<?php
namespace rp\api;

use rp\Db;
use rp\Cache;
use rp\Url;
use rp\Config;
use rp\Hook;

class Logs extends Base{
	
	private $limit;
	private $tagesData;
	private $cateData;
	
	public function __construct(){
		parent::__construct();
		$this->limit=!empty(Config::get('webConfig.pagesize')) ? Config::get('webConfig.pagesize') : 10;
		$this->tagesData=Cache::read('tages');
		$this->cateData=Cache::read('category');
	}
	
	public function getList(){
		$this->chechAuth(true);
		$cateId=input('cate');//支持多分类，如：1,2,3
		$authorId=(int)input('author');
		$date=input('date');//支持202102和20210325
		$tag=(int)input('tag');
		$key=(string)input('q');
		$page=(int)input('page') ? (int)input('page') : 1;
		$where=array('a.status'=>0);
		$wherestr=array();
		if(!empty($cateId)){
			if(!is_array($cateId)){
				$cateId=array((int)$cateId);
			}
			$cateId=arrayIdFilter($cateId);
			$where['a.cateId']=array('in',$cateId);
		}
		if(self::$user['role'] != 'admin'){
			$where['a.authorId']=self::$user['id'];
		}elseif(!empty($authorId)){
			$where['a.authorId']=$authorId;
		}
		if(!empty($date)){
			if(strlen($date) == 6){
				$date2=date('Ym',strtotime($date.'01'));
				$wherestr[]='DATE_FORMAT(a.createTime,"%Y%m") = "'.$date2.'"';
			}else{
				$date=str_pad($date,8,0,STR_PAD_RIGHT);
				$date2=date('Ymd',strtotime($date));
				$wherestr[]='DATE_FORMAT(a.createTime,"%Y%m%d") = "'.$date2.'"';
			}
		}
		if(!empty($tag)){
			$tag=arrayIdFilter($tag);
			$where['a.tages']=array('find_in_set',$tag);
		}
		if(!empty($key)){
			$key=strip_tags(strDeep($key));
			$where['a.title']=array('like','%'.$key.'%');
		}
		$order=$this->getOrder(array('id'=>'a','isTop'=>'a','views'=>'a','comnum'=>'a','upnum'=>'a','upateTime'=>'a','createTime'=>'a'));
		
		$count=Db::name('logs')->alias('a')->join(array(
			array('category b','a.cateId=b.id','left'),
			array('user c','a.authorId=c.id','left'),
		))->where($where)->where(join(' and ',$wherestr))->field('a.id')->count();
		
		$list=Db::name('logs')->alias('a')->join(array(
			array('category b','a.cateId=b.id','left'),
			array('user c','a.authorId=c.id','left'),
		))->where($where)->where(join(' and ',$wherestr))->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.isTop,a.views,a.comnum,a.upnum,a.upateTime,a.createTime,a.status,b.cate_name as cateName,c.nickname as author')->limit(($page-1)*$this->limit.','.$this->limit)->order($order)->select();
		foreach($list as $k=>$v){
			$list[$k]['url'] = Url::logs($v['id']);
			$list[$k]['cateUrl'] = Url::cate($v['cateId']);
			$list[$k]['cateLogNum'] = isset($this->cateData[$v['cateId']]) ? $this->cateData[$v['cateId']]['logNum'] : 0;
			$list[$k]['tagesData'] = $this->getTages($v['tages']);
		}
		Hook::doHook('api_logs_list',array(&$list));
		$page=array('count'=>$count,'pageAll'=>ceil($count / $this->limit),'limit'=>$this->limit,'pageNow'=>$page);
		$this->response(array('list'=>$list,'pageBar'=>$page));
	}
	
	public function getData(){
		$id=(int)input('id');
		$data=Db::name('logs')->where('id='.$id)->find();
		if(empty($data) || $data['status'] != 0){
			$this->response('',404,'文章不存在或未发布！');
		}
		$category=Cache::read('category');
		$data['cateName']=isset($category[$data['cateId']]['cate_name']) ? $category[$data['cateId']]['cate_name'] : '未分类';
		$tages=Cache::read('tages');
		$user=Cache::read('user');
		$tagName=array();
		$tagArr=explode(',',$data['tages']);
		foreach($tagArr as $v){
			if(isset($tages[$v])){
				$tagName[]=array(
					'id'=>$v,
					'name'=>$tages[$v]['tagName'],
					'url'=>Url::tag($v),
				);
			}
		}
		$data['tages']=$tagName;
		$data['cateUrl']=!empty($data['cateId']) ? Url::cate($data['cateId']) : '';
		$data['author']=$user[$data['authorId']]['nickname'];
		$data['authorUrl']=Url::other('author',$data['authorId']);
		$data['extend'] =json_decode($data['extend'],true);
		Hook::doHook('api_logs_detail',array(&$data));
		unset($data['extend']);
		unset($data['password']);
		$this->response($data);
	}
	
	public function praise(){
		$id=(int)input('id');
		$data=Db::name('logs')->where('id='.$id)->field('status,upnum')->find();
		if(empty($data) || $data['status'] != 0){
			$this->response('',404,'文章不存在或未发布！');
		}
		$lastTime=cookie('me_praise_'.$id);
		if(!empty($lastTime)){
			$this->response('',401,'你已点过赞了！');
		}
		$res2=Db::name('logs')->where('id='.$id)->setInc('upnum');
		if($res2){
			cookie('me_praise_'.$id,$id,365*24*60*60);
			$this->response(array('num'=>$res['upnum'] + 1,'result'=>'点赞成功，感谢您的支持！'),200);
		}
		$this->response('',401,'点赞失败！');
	}
	
	public function post(){
		$this->chechAuth(true);
		$param=input('post.');
		$default=array(
			'id'=>0,
			'title'=>'',
			'content'=>'',
			'excerpt'=>'',
			'keywords'=>'',
			'cateId'=>'',
			'authorId'=>'',
			'specialId'=>'',
			'alias'=>'',
			'password'=>'',
			'template'=>'',
			'createTime'=>'',
			'isTop'=>'',
			'isRemark'=>'',
			'extend'=>'',
			'tagesName'=>'',
			'status'=>0,
			'type'=>3,
		);
		$param=array_merge($default,$param);
		$logid=intval($param['id']) ? intval($param['id']) : 0;
		if(self::$user['role'] != 'admin'){
			$param['authorId']=self::$user['id'];
		}
		if(!empty($logid) && self::$user['role'] != 'admin'){
			$data=Db::name('logs')->where('id='.$logid)->field('authorId')->find();
			(empty($data) || $data['authorId'] != self::$user['id']) && $this->response('',401,'无权限操作！');
		}
		$data=array();
		$data['title']=strip_tags($param['title']);
		$data['content']=clear_html($param['content'],array('script'));
		if(empty($data['title'])){
			$this->response('',401,'标题不能为空！');
		}
		if(empty($data['content'])){
			$this->response('',401,'正文不能为空！');
		}
		$data['excerpt']=!empty(strip_tags($param['excerpt'])) ? strip_tags($param['excerpt']) : getContentByLength($param['content']);
		$data['keywords']=str_replace('，',',',strip_tags($param['keywords']));
		$data['cateId']=intval($param['cateId']);
		$data['authorId']=intval($param['authorId']);
		$data['specialId']=intval($param['specialId']);
		$data['alias']=strip_tags($param['alias']);
		$data['password']=strip_tags($param['password']);
		$data['template']=strip_tags($param['template']);
		$data['createTime']=!empty($param['createTime']) ? date('Y-m-d H:i:s',strtotime($param['createTime'])) : date('Y-m-d H:i:s');
		$data['upateTime']=date('Y-m-d H:i:s');
		$data['isTop']=!empty($param['isTop']) ? intval($param['isTop']) : 0;
		$data['isRemark']=!empty($param['isRemark']) ? intval($param['isRemark']) : 0;
		$data['extend']=$this->extendPost($param);
		$data['status']=intval($param['type']) == 3 ? intval($param['status']) : intval($param['type']);
		$this->checkAlias($data['alias']);
		$this->checkTemplate($data['template']);
		$logAlias=Cache::read('logAlias');
		if($param['type'] != 2){
			$data['tages']=$this->replaceTages($param['tagesName']);
		}
		if(!empty($logid)){
			$key=array_search($data['alias'],$logAlias);
			if(!empty($data['alias']) && ($key && $key != $logid)){
				$this->response('',401,'别名重复，请更换别名！');
			}
			$res=Db::name('logs')->where('id='.$logid)->update($data);
		}else{
			if(!empty($data['alias']) && array_search($data['alias'],$logAlias)){
				$this->response('',401,'别名重复，请更换别名！');
			}
			$logid=Db::name('logs')->insert($data);
		}
		if(!empty($data['specialId'])){
			Db::name('special')->where('id='.$data['specialId'])->update(array('updateTime'=>date('Y-m-d H:i:s')));
		}
		if($param['type'] != 2){
			$this->updateCache();
		}
		Hook::doHook('api_logs_save',array($logid));
		$this->response($logid,200,'操作成功！');
	}
	
	public function dele(){
		$this->chechAuth(true);
		$ids=input('post.ids');
		$idsArr=explode(',',$ids);
		foreach($idsArr as $k=>$v){
			if(!intval($v)) unset($idsArr[$k]);
		}
		if(empty($idsArr)){
			$this->response('',401,'无效参数！');
		}
		if(self::$user['role'] != 'admin'){
			$idsSelect=Db::name('logs')->where(array('authorId'=>self::$user['id'],'id'=>array('in',join(',',$idsArr))))->field('id')->select();
			$idsArr=array_column($idsSelect,'id');
		}
		$ids=join(',',$idsArr);
		$res=Db::name('logs')->where(array('id'=>array('in',$ids)))->dele();//删除文章
		$res2=Db::name('attachment')->where(array('logId'=>array('in',$ids)))->dele();//删除附件
		$res2=Db::name('comment')->where(array('logId'=>array('in',$ids)))->dele();//删除评论
		$this->updateCache();
		Hook::doHook('api_logs_dele',array($ids));
		$this->response($ids,200,'操作成功！');
	}
	
	private function getTages($tags){
		$tagData=array();
		$tagArr=explode(',',$tags);
		foreach($tagArr as $v){
			if(isset($this->tagesData[$v])){
				$tagData[]=array(
					'id'=>$v,
					'name'=>$this->tagesData[$v]['tagName'],
					'url'=>Url::tag($v),
				);
			}
		}
		return $tagData;
	}
	
	private function replaceTages($tages){
		$tages = str_replace(array(';','，','、'), ',', $tages);
		$tages = RemoveSpaces(strip_tags($tages));
		$tagesArr = explode(',', $tages);
		$tagesArr = array_unique(array_filter($tagesArr));
		if(empty($tagesArr)) return '';
		$tagesArr = array_slice($tagesArr, 0, 10);//最多10个标签
		$data=array();
		$tagesAll=Cache::read('tages');
		$tagesAll=array_column($tagesAll,NULL,'tagName');
		foreach($tagesArr as $value){
			if(isset($tagesAll[$value])){
				$data[]=$tagesAll[$value]['id'];
			}else{
				$data[]=Db::name('tages')->insert(array('tagName'=>$value));
			}
		}
		return join(',',$data);
	}
	
	private function updateCache(){
		Cache::update('tages');
		Cache::update('category');
		Cache::update('special');
		Cache::update('total');
		Cache::update('logRecord');
		Cache::update('logAlias');
	}
}
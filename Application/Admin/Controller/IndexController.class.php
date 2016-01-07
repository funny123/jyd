<?php
/*
 * @thinkphp3.2.2  客户管理   php5.3以上
 * @Created on 2015/12/18
 * @Author  funny   524656859@qq.com
 * @如果需要公共控制器，就不要继承AuthController，直接继承Controller
 */
namespace Admin\Controller;
use Common\Controller\AuthController;
use Think\Auth;

//权限控制类
class IndexController extends AuthController {
	
	//首页
	public function index(){
		$m = M('auth_rule');
		$field = 'id,name,title';
		$where['pid'] = 0;		//顶级ID
		$where['status'] = 1;	//显示状态
	    $data = $m->field($field)->where($where)->select();	   
	    $auth = new Auth();
	    //没有权限的菜单不显示
    	foreach ($data as $k=>$v){   		
    		if(!$auth->check($v['name'], session('aid')) && session('aid') != 1){
    			unset($data[$k]);
    		}else{
    			// status = 1    为菜单显示状态
    			$data[$k]['sub'] = $m->field($field)->where('pid='.$v['id'].' AND status=1')->select();
    			$data[$k]['default_name'] = $data[$k]['sub']['0']['name'];
    			$data[$k]['default_title'] = $data[$k]['sub']['0']['title'];
    			foreach ($data[$k]['sub'] as $k2 => $v2){				
    				if(!$auth->check($v2['name'], session('aid')) && session('aid') != 1){
    					unset($data[$k]['sub'][$k2]);
    				}
    			}
    		}
	    }
	    $this->assign('data',$data);	// 顶级
    	$this->display();
	}
	
	//后台首页
    public function main(){
    	$this->display();
    }
    
    //修改密码
    public function edit_pwd(){
    	if(!empty($_POST)){
    		$m = M('admin');
    		$where['id'] = session('aid');
    		$where['password'] = md5(I('old_pwd'));
    		$new_pwd = md5(I('new_pwd'));
    		$data = $m->field('id')->where($where)->find();
    		if(empty($data)){
    			$this->ajaxReturn(0);	//失败，原密码错误
    		}else{
    			$result = $m->where('id='.$where['id'])->data('password='.$new_pwd)->save();
    			if($result){
    				session('aid',null);
    				session('account',null);
    				$this->ajaxReturn(1);	//修改成功
    			}else{
    				$this->ajaxReturn(2);	//更新失败
    			}
    		}
    	}else{
    		$this->display();
    	}   	
    }

    //循环删除目录和文件函数
    public function delDirAndFile($dirName){
    	if ( $handle = opendir( "$dirName" ) ) {
    		while ( false !== ( $item = readdir( $handle ) ) ) {
    			if ( $item != "." && $item != ".." ) {
    				if ( is_dir( "$dirName/$item" ) ) {
    					delDirAndFile( "$dirName/$item" );
    				} else {
    					unlink( "$dirName/$item" );
    				}
    			}
    		}
    		closedir( $handle );
    		if( rmdir( $dirName ) ) return true;
    	}
    }
 
    //清除缓存
    public function clear_cache(){
    	$str = I('clear');	//防止搜索到第一个位置为0的情况
    	if($str){
			//strpos 参数必须加引号
    		//删除Runtime/Cache/admin目录下面的编译文件
    		if(strpos("'".$str."'", '1')){   			
    			$dir = APP_PATH.'Runtime/Cache/Admin/';
    			$this->delDirAndFile($dir);
    		}
    		//删除Runtime/Cache/Home目录下面的编译文件
    		if(strpos("'".$str."'", '2')){    			
    			$dir = APP_PATH.'Runtime/Cache/Home/';
    			$this->delDirAndFile($dir);
    		}
    		//删除Runtime/Data/目录下面的编译文件
    		if(strpos("'".$str."'", '3')){
    			$dir = APP_PATH.'Runtime/Data/';
    			$this->delDirAndFile($dir);
    		}
    		//删除Runtime/Temp/目录下面的编译文件
    		if(strpos("'".$str."'", '4')){	
    			$dir = APP_PATH.'Runtime/Temp/';
    			$this->delDirAndFile($dir);
    		}
    		$this->ajaxReturn(1);	//成功
    	}else{
    		$this->display();
    	}
    }

    //退出登录
    public function logout(){
    	session('aid',null);	//注销 uid ，account
    	session('account',null);
    	$this->success('退出登录成功',U('Public/login'));
    }    
}





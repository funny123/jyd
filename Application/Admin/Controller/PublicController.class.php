<?php
/*
 * @thinkphp3.2.2  客户管理   php5.3以上
 * @Created on 2015/12/18
 * @Author  funny   524656859@qq.com
 * @如果需要公共控制器，就不要继承AuthController，直接继承Controller
 */
namespace Admin\Controller;
use Think\Controller;

//不验证的控制器
class PublicController extends Controller {
   
    //ajxa检查验证码
    public function check_code(){
    	$code = $_GET['code'];	//验证码
    	$verify = new \Think\Verify();
    	if($verify->check($code)){
    		$this->ajaxReturn(1);	//成功
    	}else{
    		$this->ajaxReturn(0);	//失败
    	}
    }
    //登录验证
    public function login(){
    	if(!empty($_POST)){
    		$map['account'] = I('account');   //用户名
    		$map['password'] = md5(I('password'));	//密码
    		$m = M('admin');
    		$result = $m->field('id,account,password,login_count,status')->where($map)->find();
    		if($result){
    			if($result['status'] == 0){
    				$this->error('登录失败，账号被禁用',U('Public/login'));
    			}
    			session('aid',$result['id']);	//管理员ID
    			session('account',$result['account']);	//管理员帐号
                session('password',$result['password']);	//管理员密码
    			//保存登录信息
    			$data['id'] = $result['id'];	//用户ID
    			$data['login_ip'] = get_client_ip();	//最后登录IP
    			$data['login_time'] = time();		//最后登录时间
    			$data['login_count'] = $result['login_count'] + 1;
    			$m->save($data);    				
    			$this->success('验证成功，正在跳转到首页',U('Index/index'));			
    		}else{
    			$this->error('账户或密码错误',U('Public/login'));
    		}
    	}else{
    		if(session('aid')){
    			$this->error('已登录，正在跳转到主页',U('Index/index'));
    		}
    		$this->display();
    	}
    }
    
    //验证码
    public function verify(){   	
    	ob_clean();		//清除缓存
    	$Verify = new \Think\Verify();
    	$Verify->fontSize = 30;	//验证码字体大小
    	$Verify->length = 4;	//验证码位数
    	$Verify->entry();
    }

}





<?php
/*
 * @thinkphp3.2.2  客户管理   php5.3以上
 * @Created on 2015/12/18
 * @Author  funny   524656859@qq.com
 * @如果需要公共控制器，就不要继承AuthController，直接继承Controller
 */
namespace Admin\Controller;
use Think\Controller;
use Org\Util;



//后台管理员
class MemberController extends Controller {
	
	//用户列表
    public function member_list(){
    	$m = M('Member');
        $where = '';
        if(!empty($_REQUEST['keyword'])){
            $where .= "(agentname like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%' or realname like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%' or loginname like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%' or department like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%') ";
        }
    	// page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
    	$data = $m->order('id ASC')->page($_GET['p'].','.PAGE_SIZE)->where($where)->select();

//        dump($data);exit;
    	$this->assign('data',$data);
    	//分页 
    	$count = $m->count(id);		// 查询满足要求的总记录数
    	$page = new \Think\Page($count,PAGE_SIZE);		// 实例化分页类 传入总记录数和每页显示的记录数
    	$show = $page->show();		// 分页显示输出
    	$this->assign('page',$show);// 赋值分页输出
    	$this->display();
    }
//查看客户完整手机号
public function viewtel($id=''){
    $m = M('auth_rule');
    $field = 'id,name,title';
    $where['pid'] = 0;		//顶级ID
    $where['status'] = 1;	//显示状态
    $data = $m->field($field)->where($where)->select();
    $auth = new \Think\Auth();
    //没有权限的菜单不显示
        if(!$auth->check('Member/viewtel', session('aid')) && session('aid') != 1){
            echo '没有权限查看手机号';
        }else{
            $m=M('member');
            $id=I('id');
            $info=$m->field('tel,loginname,realname')->where('id='.$id)->find();
//            echo $info['tel'];
            $this->assign('data',$info);
            $this->display();
        }
}

//检查账号是否已注册
    public function check_password(){
        $m = M('admin');
        $where['password'] = I('password');	//账号
        $where['loginname'] =session();
        $data = $m->where($where)->find();
        if(empty($data)){
            $this->ajaxReturn(0);   //不存在
        }else{
            $this->ajaxReturn(1);	//存在
        }
    }
    //检查账号是否已注册
    public function check_loginname(){
    	$m = M('member');
    	$where['loginname'] = I('loginname');	//账号
    	$data = $m->field('id')->where($where)->find();
    	if(empty($data)){
    		$this->ajaxReturn(0);   //不存在
    	}else{
    		$this->ajaxReturn(1);	//存在
    	}
    }
        
    //添加用户
    public function member_add()
    {
        if (!empty($_POST)) {
            $m = M('member');
            $data['loginname'] = I('loginname');
            //$data['codeid'] = (I('codeid'));
            //$data['time'] = time();        //创建时间
            $where['loginname'] = I('loginname');
//            dump($data);
            $result = $m->where($where)->find();
            if (!empty($result)) {
                $this->ajaxReturn(0);  //用户名重复
            }
            //添加用户
            $result['uid']  = $m->add($_POST);
            $result['group_id'] = I('group_id');	//用户组ID
            if($result['uid']){
                $m = M('auth_group_access');
                //分配用户组
                if($m->add($result)){
                    $this->ajaxReturn(1);	//分配用户组成功
                }else{
                    $this->ajaxReturn(2);	//分配用户组失败
                }
            }else{
                $this->ajaxReturn(0);  //添加用户失败
            }
        }else{
            $m = M('auth_group');
            $data = $m->field('id,title')->select();
            $this->assign('data',$data);
            $this->display();
        }


    }
    //用户列表
    public function edit_mul(){
        $m = M('Member');
        $where = '';

        if(!empty($_REQUEST['keyword'])){
            $where .= "(agentname like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%' or realname like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%' or loginname like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%' or department like '%".htmlspecialchars(trim($_REQUEST['keyword']))."%') ";
        }
        // page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
        $data = $m->order('id ASC')->page($_GET['p'].',99')->where($where)->field('id,realname,agentname,group,department')->select();

//        dump($data);exit;
        $this->assign('data',$data);
        //分页
        $count = $m->count(id);		// 查询满足要求的总记录数
        $page = new \Think\Page($count,99);		// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $page->show();		// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
//   导入excel会员到数据库
// 文件上传
    public function add_excel_ok() {
        //import('ORG.Util.UploadF');

       //$upload = new Upload();// 实例化上传类
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize  = 3145728 ;// 设置附件上传大小
        $upload->allowExts  = array('xlsx', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =      './Public/';
        $upload->savePath =  '';// 设置附件上传目录
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功

            //$info=$upload->getUploadFileInfo();
//            dump($info);
            $loadfile=$upload->rootPath.$info['file']['savepath'].$info['file']['savename'];
//            echo $loadfile;exit;
//            导入上传excel文件到数据库
            if($info) {
                // echo 'ok';
                $str = '';
                $strs = array();
                import("Org.Util.PHPExcel.IOFactory");
                import("Org.Util.PHPExcel.Reader.Excel2007");
                import("Org.Util.PHPExcel");
                $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                $objPHPExcel = $objReader->load($loadfile);
                $sheet = $objPHPExcel->getSheet(0);
                $highestRow = $sheet->getHighestRow();           //取得总行数
                $highestColumn = $sheet->getHighestColumn(); //取得总列数

                $fieldArr = array('id', 'loginname', 'moniname', 'time', 'realname', 'sex', 'codeid', 'tel', 'email', 'address',
                    'agentname', 'group', 'department', 'birthday', 'jihuotime', 'jihuomoney', 'loginuser', 'remark', 'kaihuform');
                $j = count($fieldArr);
                $objWorksheet = $objPHPExcel->getActiveSheet();
                $highestRow = $objWorksheet->getHighestRow();
                echo 'highestRow='.$highestRow;
                echo "<br>";
                $highestColumn = $objWorksheet->getHighestColumn();
                $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
                echo 'highestColumnIndex='.$highestColumnIndex;
                echo "<br>";
                $headtitle=array();
                for ($row = 3;$row <= $highestRow;$row++)
                {
                    $strs=array();
                    //注意highestColumnIndex的列数索引从0开始
                    for ($col = 0;$col < $highestColumnIndex;$col++)
                    {
                        $strs[$col] =$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                            $data[$fieldArr[$col]]=$strs[$col];
                    }
                   //dump($data);exit;
                    $result=M('Member')->add($data);
                }
            }
            //$this->success('成功！');
        }
    }
                    //编辑
    public function member_edit()
    {
        $id = intval($_REQUEST['id']);
        if ($_POST) {
            $data = $_POST;
            $data['id']=$id;
           $result=M('member')->save($data);
            if($result){
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(0);
            }
        } else {
            $id = intval($_REQUEST['id']);
            $info = M('member')->where('id=' . $id)->find();
            $this->assign('vo', $info);
            $this->display();
        }
    }


    //删除用户
    public function member_del(){
        $id = intval($_REQUEST['id']);	//用户ID

    	$m = M('member');
        $result = $m->where('id='.$id)->delete();
    	if ($result){
    		$this->ajaxReturn(1);	//成功
    	}else {
    		$this->ajaxReturn(0);	//删除失败
    	}
    }

    //角色-组
    public function auth_group(){
    	$m = M('auth_group');
    	$data = $m->order('id DESC')->select();
    	$this->assign('data',$data);
    	$this->display();
    }
    
    //添加组
    public function group_add(){
    	if(!empty($_POST)){
    		$data['rules'] = I('rules');
    		if(empty($data['rules'])){
    			$this->error('权限不能为空');
    		}
    		$m = M('auth_group');
    		$data['title'] = I('title');
    		$data['rules'] = implode(',', $data['rules']);
    		$data['create_time'] = time();
    		if($m->add($data)){
    			$this->success('添加成功',U('Admin/auth_group'));
    		}else{
    			$this->error('添加失败');
    		}
    	}else{
    		$m = M('auth_rule');
	    	$data = $m->field('id,name,title')->where('pid=0')->select();
	    	foreach ($data as $k=>$v){
	    		$data[$k]['sub'] = $m->field('id,name,title')->where('pid='.$v['id'])->select();
	    	}
	    	$this->assign('data',$data);	// 顶级
    		$this->display();
    	}	
    }
    
    //编辑组
    public function group_edit(){
    	$m = M('auth_group');
    	if(!empty($_POST)){
    		$data['id'] = I('id');
    		$data['title'] = I('title');
    		$data['rules'] = implode(',', I('rules'));
    		if($m->save($data)){
    			$this->success('修改成功');
    		}else{
    			$this->error('修改失败');
    		}
    	}else{
    		$where['id'] = I('id');	//组ID
    		$reuslt = $m->field('id,title,rules')->where($where)->find();
    		$reuslt['rules'] = ','.$reuslt['rules'].',';
    		$this->assign('reuslt',$reuslt);

     		$m = M('auth_rule');
    		$data = $m->field('id,title')->where('pid = 0')->select();
    		$arr = array();
    		foreach ($data as $k => $v){
    			$data[$k]['sub'] = $m->field('id,title')->where('pid ='.$v['id'])->select();
    		}
    		$this->assign('data',$data);
    		$this->display();    		
    	}
    }

    //删除组
    public function group_del(){
    	$where['id'] = I('id');
    	$m = M('auth_group');
    	if($m->where($where)->delete()){
    		$this->ajaxReturn(1);
    	}else{
    		$this->ajaxReturn(0);
    	}
    }
         
    //权限列表
    public function auth_rule(){
    	if(!empty($_POST)){
    		$m = M('auth_rule');
    		$data['name'] = I('name');
    		$data['title'] = I('title');
    		$data['pid'] = I('pid');
    		$data['create_time'] = time();
    		if($m->add($data)){
    			$this->success('添加成功');	//成功
    		}else{
    			$this->success('添加失败');	//失败
    		}
    	}else{
    		$m = M('auth_rule');
    		$field = 'id,name,title,create_time,status,sort';
	    	$data = $m->field($field)->where('pid=0')->select();
	    	foreach ($data as $k=>$v){
	    		$data[$k]['sub'] = $m->field($field)->where('pid='.$v['id'])->select();
	    	}
	    	$this->assign('data',$data);	// 顶级
	    	$this->display();
    	}
    }

    // 文件上传
    public function add_excel_ok1() {
        //import('ORG.Util.UploadF');

        //$upload = new Upload();// 实例化上传类
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize  = 3145728 ;// 设置附件上传大小
        $upload->allowExts  = array('xlsx', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =      './Public/';
        $upload->savePath =  '';// 设置附件上传目录
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功

            //$info=$upload->getUploadFileInfo();
//            dump($info);
            $loadfile=$upload->rootPath.$info['file']['savepath'].$info['file']['savename'];
//            echo $loadfile;exit;
//            导入上传excel文件到数据库
            if($info) {
                // echo 'ok';
                $str = '';
                $strs = array();
                import("Org.Util.PHPExcel.IOFactory");
                import("Org.Util.PHPExcel.Reader.Excel2007");
                import("Org.Util.PHPExcel");
                $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                $objPHPExcel = $objReader->load($loadfile);
//                $sheet = $objPHPExcel->getSheet(0);
//                $highestRow = $sheet->getHighestRow();           //取得总行数
//                $highestColumn = $sheet->getHighestColumn(); //取得总列数

                $fieldArr = array('id', 'loginname', 'moniname', 'time', 'realname', 'sex', 'codeid', 'tel', 'email', 'address',
                    'agentname', 'group', 'department', 'birthday', 'jihuotime', 'jihuomoney', 'loginuser', 'remark', 'kaihuform');
                $j = count($fieldArr);
                $objWorksheet = $objPHPExcel->getActiveSheet();
                $highestRow = $objWorksheet->getHighestRow();
//                echo 'highestRow='.$highestRow;
//                echo "<br>";
//                $highestColumn = $objWorksheet->getHighestColumn();
//                $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
//                echo 'highestColumnIndex='.$highestColumnIndex;
//                echo "<br>";
                $headtitle = array();
                $sheetCount = $objPHPExcel->getSheetCount();
                for($s=0;$s<$sheetCount;$s++){
                    $objWorksheet = $objPHPExcel->getSheet($s);
                    $highestRow = $objWorksheet->getHighestRow();
                    echo '第'.$s.'张表'.'<br>';
               echo 'highestRow='.$highestRow;
                echo "<br>";
               $highestColumn = $objWorksheet->getHighestColumn();
                $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
                echo 'highestColumnIndex='.$highestColumnIndex;
                echo "<br>";
                for ($row = 3; $row <= $highestRow; $row++) {
                    $strs = array();
                    //注意highestColumnIndex的列数索引从0开始
                    for ($col = 0; $col < $highestColumnIndex; $col++) {
                        $strs[$col] = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                        $data[$fieldArr[$col]] = $strs[$col];
                    }
                    //dump($data);exit;
                    $result = M('Member')->add($data);
                }

            }
            }
            //$this->success('成功！');
        }
    }
    //
}





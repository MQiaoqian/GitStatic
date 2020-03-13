<?
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
* 白嫖这种好事为什么不整个这个
*
* @package GitStatic
* @author 乔千
* @version 1.0.0
* @link https://blog.mumuli.cn
*/
class GitStatic_Plugin implements Typecho_Plugin_Interface
{
  /**
  * 激活插件方法,如果激活失败,直接抛出异常
  *
  * @access public
  * @return void
  * @throws Typecho_Plugin_Exception
  */
  public static function activate()
    {
      //挂载函数
      //上传
      Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('GitStatic_Plugin', 'uploadHandle');
      //修改
      //Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('GitStatic_Plugin', 'modifyHandle');
      //删除
      //Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('GitStatic_Plugin', 'deleteHandle');
      //路径参数处理
      Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('GitStatic_Plugin', 'attachmentHandle');
      //文件内容数据
      //Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('GitStatic_Plugin', 'attachmentDataHandle');

      return '插件启用成功啦~';
    }
    /**
    * 禁用插件方法,如果禁用失败,直接抛出异常
    *
    * @static
    * @access public
    * @return void
    * @throws Typecho_Plugin_Exception
    */
    public static function deactivate()
      {
        return '插件关闭';
      }

      /**
      * 获取插件配置面板
      *
      * @access public
      * @param Typecho_Widget_Helper_Form $form 配置面板
      * @return void
      */
      public static function config(Typecho_Widget_Helper_Form $form)
        {
          $token = new Typecho_Widget_Helper_Form_Element_Text('token',
          null, null,
          _t('Git仓库token'),
          _t('请登录Github获取'));
          $form->addInput($token->addRule('required', _t('token不能为空！')));

          $passname = new Typecho_Widget_Helper_Form_Element_Text('passname',
          NULL, Null,
          _t('用户名：'),
          _t('例如MQiaoqian'));
          $form->addInput($passname->addRule('required', _t('用户名不能为空！')));

          $repos = new Typecho_Widget_Helper_Form_Element_Text('repos',
          NULL, Null,
          _t('储存桶：'),
          _t('例如MCDN'));
          $form->addInput($repos->addRule('required', _t('储存桶不能为空！')));
        }

        /**
        * 个人用户的配置面板
        *
        * @access public
        * @param Typecho_Widget_Helper_Form $form
        * @return void
        */
        public static function personalConfig(Typecho_Widget_Helper_Form $form)
          {
          }

          /**
          * 插件实现方法
          *
          * @access public
          * @return void
          */
          public static function uploadHandle($file)
            {
              //例如 https://cdn.jsdelivr.net/gh/MQiaoqian/MCDN/EJsnMv8PX157y9k.jpg 
              if (empty($file['name'])) return false;
              //获取扩展名 
              $ext = self::getSafeName($file['name']);
              //判定是否是允许的文件类型
              if (!Widget_Upload::checkFileType($ext)) return false;
              //获取文件名
              $filePath = '/' . date('Y') . '/' . date('m') . '/' . date('d') . '/';
              $fileName = time() . '.' . $ext;
              //cos上传文件的路径+名称
              $newPath=$filePath.$fileName;
              //获取插件参数
              $options = Typecho_Widget::widget('Widget_Options')->plugin('GitStatic');
              //如果没有临时文件名检查是否有二进制流，如果都没有则返回失败
              if(isset($file['tmp_name'])){
                $srcPath = $file['tmp_name'];
                $handle = fopen($srcPath, "r");
                $contents = fread($handle, $file['size']);//获取二进制数据流
                fclose($handle);}
                elseif(isset($file['bytes'])){$contents =base64_decode($file['bytes']);} //无临时文件，有二进制流直接上传二进制流
                else{return false;}
               
//file_put_contents('end.log', date('H:i:s')  . "\n");
            self::uploadgit($options->token,$options->passname,$options->repos,$contents,$newPath);
            return array(
            'name' => $file['name'],
            'path' => $newPath,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @Typecho_Common::mimeContentType("https://cdn.jsdelivr.net/gh/".$options->passname."/".$options->repos. $newPath),
            );
          }
         private static function uploadgit($token,$username,$repos,$files,$path)
            {
            //  var_dump($token);
             // var_dump($username);
            //  var_dump($repos);
            //  var_dump($files);
             // var_dump($path);
              $data='{
 "message": "upload a new files",
              "committer": {
                "name": "Qiaoqian",
                "email": "admin@moee.fun"
              },
              "content": "'.base64_encode($files).'"
            }';
            $curl_url = "https://api.github.com/repos/".$username."/".$repos."/contents".$path;
            $curl_token_auth = 'Authorization: token ' . $token;
            $ch = curl_init($curl_url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'User-Agent: $username', $curl_token_auth ));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

            $response = curl_exec($ch); 
            curl_close($ch);
           //  $response = json_decode($response);
           // var_dump($response);
          }
          private static function getSafeName($name)
            {
              $name = str_replace(array('"', '<', '>'), '', $name);
              $name = str_replace('\\', '/', $name);
              $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
              $info = pathinfo($name);
              $name = substr($info['basename'], 1);
              return isset($info['extension']) ? strtolower($info['extension']) : '';
            }
          

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content)
    {
     //$tmp = preg_match('/http(s)?:\/\/[\w\d\.\-\/]+$/is', $domain);    //粗略验证域名
        //if (!$tmp) return false;
        $options = Typecho_Widget::widget('Widget_Options')->plugin('GitStatic');
        return Typecho_Common::url($content['attachment']->path, "https://cdn.jsdelivr.net/gh/".$options->passname."/".$options->repos);
    }
          }

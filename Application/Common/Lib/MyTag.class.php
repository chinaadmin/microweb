<?php
/**
 * 自定义标签
 * @author xiongzw
 * @date 2015-03-30
 */
namespace Common\Lib;
use Think\Template\TagLib;
class MyTag extends TagLib{
	//标签定义
	protected $tags   =  array(
			// 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层
			'plupload'    => array('attr'=>'use,type,style-check,style-start','close'=>0),
			'editor'      => array('attr'=>'name,mod,id,width,height','close'=>1),
			'umeditor'      => array('attr'=>'name,mod,id,width,height','close'=>1),
            'rule'=>array('attr'=>'name,module', 'close' => 1)
	);
	/**
	 * 上传标签
	 * @param  $tag
	 * @param  $content
	 * @return string
	 * use <MyTag:plupload  type='one' use='upload'/>
	 */
	public function _plupload($tag,$content){
	   $id = $tag['id']?$tag['id']:'browse';
	   $styleCheck = $tag['style-check'];
	   $style = $tag['style'];
       $default_name = $tag['name']?$tag['name']:'attachId';
	   $type = $tag['type']?$tag['type']:'one';
	   $login = $tag['login'];
	   $model = $tag['model'];
	   $img_width = $tag['img-width']?$tag['img-width']:75;
	   $img_height = $tag['img-height']?$tag['img-height']:75;
       $name = $default_name;
       if($type!='one'){
           $name .= '[]';
       }
	   $use = $tag['use']?$tag['use']:"upload";
	   $clickdefault = $tag['clickdefault']=="true"&&$type!='one'?true:false;
	   $att_id = 'false';
	   if($tag['attachment']){
	    $att_id = $this->autoBuildVar($tag['attachment']);
	   }
	   if($tag['id_suffix']){
	   	$id_suffix = $this->autoBuildVar($tag['id_suffix']);
	   }else{
	   	$id_suffix = 1;
	   }
	   $url = $tag['url']?$tag['url']:"";
	   $thumb = $tag['thumb']=="true"?true:false;       //是否需要缩略图
	   $thumbwidth = $tag['thumbwidth']?$tag['thumbwidth']:""; //缩略图宽度
	   $thumbheight = $tag['thumbheight']?$tag['thumbheight']:""; //缩略图高度
	   $thumbtype = $tag['thumbtype']?$tag['thumbtype']:1;//缩略图类型
	   $str = <<<str
	   <script type="text/javascript">
	   window.requireJs = ["base/plugin.js"];
	   </script>
	   <div id="plupload_{$id}<?php echo {$id_suffix};?>" model="{$model}" login="{$login}" class="plupload" data-id="{$id}<?php echo {$id_suffix};?>" data-name="{$name}" style="{$style}" type="{$type}" use="{$use}" url="{$url}" thumb="{$thumb}" thumbwidth="{$thumbwidth}" thumbheight="{$thumbheight}" thumbtype="{$thumbtype}" clickdefault="{$clickdefault}">
	    <botton id="{$id}<?php echo {$id_suffix};?>" class="browse" style="{$styleCheck}">选择文件</botton>
	    <div id="progress_{$id}<?php echo {$id_suffix};?>" class="browse_progress">
		</div>
		<div class="images">
	    <?php if($att_id){?>
	      <?php foreach({$att_id}  as \$v){?>
	      	<div class='img-box'>
	      	<?php if(\$v['default']){ ?>
	      	 <P class='cover'><input type='hidden' name='{$default_name}[default]' value="<?php echo \$v['att_id'];?>"/></p>
	      	 <img width='{$img_width}' height='{$img_height}' class='img-border' src="<?php echo \$v['path'];?>" att_id="<?php echo \$v['att_id'];?>"/> 
	      	<?php }else{ ?>
	    	 <img width='{$img_width}' height='{$img_height}' src="<?php echo \$v['path'];?>" att_id="<?php echo \$v['att_id'];?>"/>   
	      	<?php } ?>
	      	<input type='hidden' value="<?php echo \$v['att_id'];?>" name='{$name}'/>
	        <span><s class='del' att_id="<?php echo \$v['att_id'];?>" data="<?php echo \$v['path'];?>"></s></span>
	      	<?php if($type=='one'){?>
	      	<a href="<?php echo \$v['path'];?>"  class="lightbox">预览</a>
	      	<?php } else{?>
	      	 <a href="<?php echo \$v['path'];?>" rel="group" class="lightbox">预览</a>
	       <?php } ?>
	      	</div>
	      <?php }?>
	    <?php } ?>
		</div>
	   </div>   
str;
	   return $str;
	}
	
	/**
	 * editor编辑器
	 * use: <MyTag:editor width="500"></MyTag:editor>
	 */
	public function _editor($tag,$content){
		$name = $tag['name']?$tag['name']:"content";
		$id = $tag['id']?$tag['id']:"editor";
		$mod = $tag['mod']?$tag['mod']:1;
		$width = $tag['width']?$tag['width']:'100%';
		$height = $tag['height']?$tag['height']:"320";
		$str = <<<str
         <script type="text/javascript">
	   window.requireJs = ["base/plugin.js"];
	   </script>
		<textarea name="{$name}" mod="{$mod}" use="editor" id="{$id}" width="{$width}" height="{$height}">
		 {$content}
	   </textarea>
str;
	   return $str;
	}
	
	/**
	 * umeditor编辑器
	 * use: <MyTag:umeditor width="500"></MyTag:editor>
	 */
	public function _umeditor($tag,$content){
		$name = $tag['name']?$tag['name']:"content";
		$id = $tag['id']?$tag['id']:"umeditor";
		$mod = $tag['mod']?$tag['mod']:1;
		$width = $tag['width']?$tag['width']:'100%';
		$height = $tag['height']?$tag['height']:"320";
		$str = <<<str
         <script type="text/javascript">
	   window.requireJs = ["base/plugin.js"];
	   </script>
		<textarea name="{$name}" mod="{$mod}" use="umeditor" id="{$id}" width="{$width}" height="{$height}">
		 {$content}
	   </textarea>
str;
		return $str;
	}

    /**
    +----------------------------------------------------------
     * rule标签解析
     * 格式： <MyTag:rule name="Index/index" module="Admin"></MyTag:rule>
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @param string $tag 标签属性
     * @param string $content 内容
    +----------------------------------------------------------
     * @return string|void
    +----------------------------------------------------------
     */
    public function _rule($tag, $content) {
        $name = $tag['name'];
        $module = $tag['module']?$tag['module']:null;//指定模块
        $parseStr   = '<?php if($__this__->checkOperate(\''.$name.'\',\''.$module.'\')): ?>';
        $parseStr  .= $content;
        $parseStr  .= '<?php endif; ?>';
        return $parseStr;
    }
}

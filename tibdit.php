<?php
tiblog("#BOF");

/**
 * Plugin Name: tibdit
 * Plugin URI: http://www.tibdit.com
 * Description: Collect tibs from readers.
 * Version: 1.3
 * Author: Justin Maxwell / Jim Smith / Laxyo Solution Softs Pvt Ltd.
 * Author URI: 
 * Text Domain: tibdit
 * Domain Path: 
 * License: GPL3
 */

/*  Copyright (C) 2014 tibdit limited

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    See <http://www.gnu.org/licenses/> for the full text of the 
    GNU General Public License.  
*/

if (!function_exists('is_admin')) 
{
	tiblog("not admin");
  header('Status: 403 Forbidden');
  header('HTTP/1.1 403 Forbidden');
  exit();
}

define( 'TIBDIT_VERSION', '1.3' );
define( 'TIBDIT_RELEASE_DATE', date_i18n( 'F j, Y', '1397937230' ) );
define( 'TIBDIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'TIBDIT_URL', plugin_dir_url( __FILE__ ) );

// include 'ChromePhp.php'; 

$plugurl = plugin_dir_url( __FILE__ );


function get_tib_token()    // On page load (wp_head) check for tib token (ie page load is tibdit callback) 
{ 

  $token = ( get_query_var( 'tibdit' ) ) ? get_query_var( 'tibdit' ) : 1;
  if($token != 1)  // if a token is found, assume this page is loading in the tibdit popup window
  {	

    // Break apart the proof-of-tib token
    $token = base64_decode($token);      
    parse_str($token, $token_content);
    extract($token_content, EXTR_OVERWRITE);

    // Determine cookie lifetime
    $options = get_option('tibdit_options');
    $acktime = $options['acktime'] ; // 1 - 30  // minutes or days
    if (substr($payaddr, 0, 1) == '1' || substr($payaddr, 0, 1) == '3' )  // mainnet - not testmode
    	$acktime = $acktime * 60 * 24 ; // minutes per day

    // insert page load javascript to set user cookie for subref, and close
    echo "<script> bd_plugin_setCookie($acktime,'$subref'); x=window.open('','_self'); x.close(); </script>";    

    tiblog("get_tib_token(): token " . var_export($token, true));

    // tiblog("callback: " . $SERVER["REQ"]);

		if (substr($subref, 0, 3) != "WP_") 
			tiblog( "get_tib_token() - not a WP_ subref! #$subref %$tibcount");  // need to force WP_ for widget
		elseif ($subref == "WP_SITE")    // [TIB_SITE]
		{   
			tiblog("get_tib_token() - site tibbed #$subref %$tibcount");  
			update_option("tib_count_".$subref, $tibcount);
		}
		elseif (substr($subref, 3,3) == "ID_" && intval(substr($subref, 6)) > 0)  // [TIB_POST]
		{
			tiblog("get_tib_token() - post tibbed #$subref %$tibcount");
			update_post_meta(intval(substr($subref, 6)), "tib_count", $tibcount);
			tiblog(get_post_meta( intval(substr($subref, 6)), "tib_count", true ));
		}
		else        // WIDGET - arbitrary subref
		{
			tiblog( "get_tib_token() - Couldn't parse WP_ token  #$subref %$tibcount ~".substr($subref, 6));
			update_option("tib_count_".$subref, $tibcount);
		}
  }
  else
  { 
  	tiblog("get_tib_token() - no tib token");
    return false; 
  }
}




  if (!class_exists("tibditWidget")) 
  {
    tiblog( "no tibditWidget:: class exists");
    class tibditWidget extends WP_Widget 
    {

      var $hastibbed = false;
      var $settings, $options_page;

      function __construct() 
      {       	
      	tiblog("tibditWidget:: __construct");
        $this->settings_field = 'tibdit_options';

      	parent::__construct( 'tibdit_widget', 'tibdit', array( 'description' => __( 'Collect tibs from readers', 'tibdit' )));
				
        if (is_admin()) 
        {
        	tiblog("tibditWidget:: __construct admin");
          // Load example settings page
          if (!class_exists("tibdit_settings"))
            include(TIBDIT_DIR . 'tibdit-settings.php');
          $this->settings = new tibdit_settings();  
        }

        include(TIBDIT_DIR . 'AddressValidator.php');
        
        add_action('init', array($this,'init') );
        add_action('admin_init', array($this,'admin_init') );
        // add_action('admin_menu', array($this,'add_admin_menu') );
        add_action('wp_head', 'get_tib_token');
				add_action('wp_enqueue_scripts', array($this,'tibdit_plugin_enqueue') );
        add_filter('query_vars', array($this,'add_query_vars_filter') );

        add_shortcode('tib_site', array($this,'tib_site_func') );
        add_shortcode('tib_post', array($this,'tib_post_func') );
        add_shortcode('tib_inline', array($this,'tib_inline_func') );

        // add_filter('the_content', "$this::that", 1);

				// register_activation_hook( __FILE__, array($this,'activate') );
				// register_deactivation_hook( __FILE__, array($this,'deactivate') );
      }
      function init() {}
      function admin_init() {}

    	// function add_admin_menu() 
    	// { 
    	// 	add_menu_page( 'tib config', 'tibdit', 'administrator', 'tibdit', 'admin_page', plugin_dir_url( __FILE__ ).'admin_icon.png' ); 
    	// }


      function add_query_vars_filter( $vars )           // For query variable
      {
      	tiblog("tibditWidget::add_query_vars_filter()");
        $vars[] = "tibdit";
        return $vars;
      }


      function tibdit_plugin() 
      {
      	tiblog("tibditWidget::tibdit_plugin()");
        parent::WP_Widget(false, $name = 'tibdit_widget');
      }


      function form($instance)    // widget form creation
      { 
        // echo "top of the form to ya";
        $options = get_option( $this->settings_field );
      	tiblog("tibditWidget::form()");
        if( $instance)          // Check values
        {
        	tiblog("form() has instance " . var_export($instance, true));   
        } 
        else 
        {
          tiblog( "form() no instance - options " . var_export($instance, true));
          $instance = wp_parse_args($instance, get_option('tibdit_options'));
          $instance['subref'] = "WP_something";

          tiblog("form() no instance - defaults set " . var_export($instance, true));   //default content for widget settings on Apearance/Widget page
        } 

        $setting = array 
      	(
      	 "title" => "Heading",
         "intro" => "Intro text",
         "subref" => "Subreference",
         "colour" => "Background shading"
        );

        foreach ($setting as $key => $label) 
        {
        	$item = array 
        	(
		    	  "ckey" => $key,
		    	  "label" => _e($label, 'tibdit'), 
		    	  "fname" => $this->get_field_name($key), 
		    	  "fid" => $this->get_field_id($key),
		    	  "value" => esc_textarea($instance[$key])
    	   	);
    	   	echo "<p><label for=$item[fid]>$item[label]</label>";
    	   	echo "<input type=text id='$item[fid]' name='$item[fname]' value='$item[value]'";
          switch ($key)
          {
            case 'title':
            case 'intro':
            case 'subref':
              echo " >";
            break;
            case 'colour':
              echo " class='bd bd-colourp' data-default-color='$item[value]' >";
            break;
          }
          echo "</p>";
				}
			}



      function update($new_instance, $old_instance) 
      {

        static $default_settings = array(  
            'title' => 'tibdit',
            'intro' => 'Please drop a microdonation in my tibjar',
            'subref' => 'WP_widget_something',
            'colour' => '#806020'
          );

        $options = get_option( $this->settings_field );

        tiblog( "tibditWidget::update() old instance:" . var_export($old_instance, true));
        tiblog( "tibditWidget::update() new instance :" . var_export($new_instance, true));
        $instance = array();

        if ($new_instance['title'])
        {
          tiblog("update() has title");
          $instance['title'] = strip_tags($new_instance['title']); 
          $instance['title'] = preg_replace_callback("/([Tt][iI][bB])([^ACE-Zace-z][\w]*|$)/", array($this, 'tibtolower'), $new_instance['title']);
        }
        elseif ($options['title'])
        {
          tiblog("update() no title but option title found");
          $instance['title'] = $options['title'];
        }
        else
        {
          tiblog("update() no title using default");          
          $instance['title'] = $default_settings['title'];
        }

        if ($new_instance['intro'])
        {
          $instance['intro'] = strip_tags($new_instance['intro']); 
          $instance['intro'] = preg_replace_callback("/([Tt][iI][bB])([^ACE-Zace-z][\w]*|$)/", array($this, 'tibtolower'), $new_instance['intro']);
        }
        elseif ($options['intro'])
          $instance['intro'] = $options['intro'];
        else
          $instance['intro'] = $default_settings['intro'];

        if ($new_instance['colour'])
          $instance['colour'] = strip_tags($new_instance['colour']);
        elseif ($options['colour'])
          $instance['colour'] = $options['colour'];
        else
          $instance['colour'] = $default_settings['colour'];

        // $instance['payaddr'] = strip_tags($new_instance['payaddr']);
        $instance['payaddr'] = $options['payaddr'];
        $instance['subref'] = strip_tags($new_instance['subref']);

        tiblog( "tibditWidget::update() end instance" . var_export($instance, true));

        return $instance;
      }

      function tibtolower($matches)
      {
        return strtolower($matches[0]);
      }

      function widget($args, $instance) // non shortcode widget output
      {
        $options = get_option( $this->settings_field );

      	tiblog( "widget() dump args: " . var_export($args, true));
        tiblog( "widget() dump instance: " . var_export($instance, true));
        tiblog( "widget() dump options: " . var_export($options, true));
        extract( $instance );
        
        $plugurl = plugin_dir_url( __FILE__ );

        $hex = $instance[colour];
        $hex = str_replace("#", "", $hex);
        if(strlen($hex) == 3) {
          $r = hexdec(substr($hex,0,1).substr($hex,0,1));
          $g = hexdec(substr($hex,1,1).substr($hex,1,1));
          $b = hexdec(substr($hex,2,1).substr($hex,2,1));
       } else {
          $r = hexdec(substr($hex,0,2));
          $g = hexdec(substr($hex,2,2));
          $b = hexdec(substr($hex,4,2));
       }

       	echo $args['before_widget']."<div class='bd widget' style='background-color: rgba($r, $g, $b, 0.2);'>";
        // echo  "<img class='beta' src='$plugurl/beta-35px.png' alt='beta'/>";

        if (!$title) $title = "tibdit";
        echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
                
        if ($intro) 
          echo "<p class='wp_widget_plugin_textarea'>$intro</p>";

		    // $add_option("tib_count_".$subref,"","","");
		    $count = get_option("tib_count_".$instance['subref']);
        tiblog("widget() option " . ".tib_count_".$instance['subref']);
       	echo tib_button( $payaddr, $subref, $count, null);

        tiblog("widget() @$payaddr #$subref %$count");

        echo "</div>".$args['after_widget'];
      }

		  function tib_site_func( $atts, $content=null )
  		  {

          $options= get_option('tibdit_options');
          $instance = shortcode_atts( array
          (
            "title" => $options['title'],
            "intro" => $options['intro'],
            "payaddr" => $options['payaddr'],
            "subref" => "WP_SITE"        
          ), $atts, "tib");


          $option= "tib_count_".$instance[subref];

          add_option($option,"","","");

  		    $html.='<div class="widget-text wp_widget_plugin_box;">'; 
  		    $html.='<p class="wp_widget_plugin_textarea">'; 
  		    $html.='</p>';   
          
          tiblog( "tib_site_func() dump atts: " . var_export($atts, true));
          tiblog( "tib_site_func() dump instance: " . var_export($instance, true));
  		  	tiblog( "tib_site_func() @$instance[payaddr] #$instance[subref] %$count");

          if (is_null($content) or $content=="")
          {
            tiblog( "tib_site_func() $subref NULL content [[$content]]". var_export($content, true));
            $html = tib_button( $instance['payaddr'], $instance['subref'], $count, null);
          }
          elseif (isset($_COOKIE["tibbed_$instance[subref]"]))
          {
            tiblog( "tib_site_func() $subref TIBBED content [[$content]]");
            $html.=$content;
            $html.=tib_button( $instance['payaddr'], $instance['subref'], $count, null);
          }
          else
          {
            tiblog( "tib_site_func() $subref HIDDEN content "); // . var_export($content, true));
            $appearance= array('readmore' => true);
            $html.=tib_button( $instance['payaddr'], $instance['subref'], $count, $appearance);
          }

  		    return $html;
  		  }


		  function tib_post_func( $atts, $content=null )
		  {
        $html="";
        $options= get_option('tibdit_options');
        $instance = shortcode_atts( array
          (
            'title' => "",
            'intro' => "",
            'payaddr' => $options['payaddr'],
            'subref' => "WP_ID_".get_the_ID(),
          ), $atts, "tib");

        $count = get_post_meta( get_the_ID(), "tib_count", true );

        tiblog( "tib_post_func() dump atts: " . var_export($atts, true));
        tiblog( "tib_post_func() dump instance: " . var_export($instance, true));
		    tiblog( "tib_post_func() @$instance[payaddr] #$instance[subref] %$count *".isset($_COOKIE["tibbed_$instance[subref]"]));
        // tiblog( "tib_post_func() NULL: " . is_null($content) . "<br>[[$content]]");

		    // the_widget('tibditWidget');

        if (is_null($content) or $content=="")
          {
            tiblog( "tib_post_func() #$instance[subref] NULL content [[$content]]". var_export($content, true));
            $html = tib_button( $instance['payaddr'], $instance['subref'], $count, null);
          }
        elseif (isset($_COOKIE["tibbed_$instance[subref]"]))
          {
            tiblog( "tib_post_func() #$instance[subref] TIBBED content [[$content]]");
            $html=$content;
            $html.=tib_button( $instance['payaddr'], $instance['subref'], $count, null);
          }
        else
          {
            tiblog( "tib_post_func() #$instance[subref] HIDDEN content"); // var_export($content, true));
            $appearance= array('readmore' => true);
            $html.=tib_button( $instance['payaddr'], $instance['subref'], $count, $appearance);
          }

        return $html;
		  }

      function tib_inline_func( $atts, $content=null )
        {
          $html="";
          $options= get_option('tibdit_options');
          $instance = shortcode_atts( array
            (
              'title' => "",
              'intro' => "",
              'payaddr' => $options['payaddr'],
              'subref' => "WP_ID_".get_the_ID()."_inline",
              'text' => " (tib) "
            ), $atts, "tib");

          tiblog( "tib_inline_func() dump atts: " . var_export($atts, true));
          tiblog( "tib_inline_func() dump instance: " . var_export($instance, true));
          tiblog( "tib_inline_func() @$instance[payaddr] #$instance[subref] %$count *".isset($_COOKIE["tibbed_$instance[subref]"]));

          if (is_null($content) or $content=="")  // no paired closing shortcode
            {
              if (!isset($_COOKIE["tibbed_$instance[subref]"])) // not already tibbed
                {
                  tiblog( "tib_inline_func() inline #$instance[subref] NULL content [[$content]]". var_export($content, true));
                  $html = "<a class='bd-link bd-live' onclick=\"bd_plugin_tib('$instance[payaddr]','$instance[subref]')\"> $instance[text] </a>";
                }
              else
                {
                  tiblog( "tib_inline_func() inline #$instance[subref] TIBBED NULL content [[$content]]". var_export($content, true));
                  $html = "";
                }
            }
          else // enclosed content
            {
              if (!isset($_COOKIE["tibbed_$instance[subref]"])) // not already tibbed
                {
                  tiblog( "tib_inline_func() inline #$instance[subref] LINK content [[$content]]". var_export($content, true));
                  $html = "<a class='bd-link bd-live' onclick=\"bd_plugin_tib('$instance[payaddr]','$instance[subref]')\">$content</a>";
                }
              else
                {
                  tiblog( "tib_inline_func() inline #$instance[subref] TIBBED LINK content [[$content]]". var_export($content, true));
                  $html = "<span class='bd-link tibbed'>$content</span>";
                }
            }
          return $html;
        }

			function tibdit_plugin_enqueue() 
        {
        	$plugurl = plugin_dir_url( __FILE__ );
        	tiblog("tibdit_plugin_enqueue() ". $plugurl);
          wp_enqueue_style( 'tibdit_plugin', $plugurl.'/tibbee.css', array(), "1.3");
          tiblog( "tibdit_plugin$plugurl/tibbee.css");
          wp_enqueue_script( 'tibdit_plugin', $plugurl.'/tib-functions.js', array(), "1.3" );
          // wp_enqueue_script( 'tibdit_plugin', $plugurl.'/tibdit-settings.js' );        
          wp_enqueue_script( 'tibdit_plugin-bottom', $plugurl.'/tib-functions-bottom.js',array(),"1.3",true );
        }

      function bd_admin_enqueue()
        {
          $plugurl = plugin_dir_url( __FILE__ );
          tiblog("bd_admin_enqueue() ". $plugurl); 
          wp_enqueue_style( 'wp-color-picker' );
          wp_enqueue_script( 'wp-color-picker' );
          wp_enqueue_script( 'bd-admin-bottom', $plugurl.'/tibdit-settings-bottom.js', array( 'wp-color-picker' ), false, true );
        }

    }
    add_action( 'widgets_init', 'register_tibdit_widget');
  }


  function register_tibdit_widget() 
  	{ 
  		tiblog("register_tibdit_widget() ");
  		register_widget('tibditWidget'); 
  	}
 

  function tib_button( $payaddr, $subref, $count, $appearance)
  {
    $html="";

    $plugurl = plugin_dir_url( __FILE__ );
    // tiblog( "tib button backtrace " .  var_export(debug_backtrace (  0 ,  2 ),true));
    tiblog(" tib_button() @$payaddr #$subref %$count ");
    // tiblog( "tib_button() dump cookie: " . var_export($_COOKIE,true));

    if (substr($payaddr,0,1) == '1')  $testmode = false;
		else  $testmode = true;

		if(isset($_COOKIE["tibbed_$subref"])) 
      { $html.="<div class='bd tibbed button'> \n"; }
    else 
      {
        $html.="<div class='bd button live' onclick=\"bd_plugin_tib('$payaddr','$subref')\"> \n";
        if ($appearance['readmore'])
          $html.="<span class='annotation'>read<br>more</span>";
      }

    if ($testmode) // button testmode icon 
      { $html.="<img src='$plugurl/testmode-icon-24px.png' alt='testmode'/>"; }

    if(isset($_COOKIE["tibbed_$subref"])) // tib graphic
      { $html.="<img src='$plugurl/already-tibbed-rect-31px.png' alt='already tibbed'/>"; }

    else
      { $html.="<img src='$plugurl/tib-button-rect-31px.png' alt='tib'/>"; }

    $html.="<span class='count'>$count</span>";  // counter


    if(! isset($_COOKIE["tibbed_$subref"]))  // tooltip
    {

      if(! $testmode)  // testmode tooltip
      {
        $html.="<div class='tip bd-testmode'>";
        $html.="<img class='callout' src='$plugurl/callout_black.png' />";  
        $html.="<p class=dict><strong>tib</strong>&ensp;/tɪb/</p>";
        $html.="<p class='detail lead'>(n)</p>";
        $html.="<p class='detail'>A microdonation or micropayment of around 15p / 25¢.</p>";
      }
      else  // realmode tooltip
      {
        $html.="<div class='tip bd-testmode'>";
        $html.="<img class='callout' src='$plugurl/callout_black.png' />"; 
        $html.="<img src='$plugurl/testmode-icon-24px.png' style='float:left; height: 1.2em; padding: 0.3em 0.3em 0 0;'>";
        $html.="<p class=detail>testmode tibs are free and carry no value</p>";
      }
      $html.="</div>";
    }

    $html.="</div> \n";

    tiblog("plugindir: .$plugurl");
  	return $html;
	 }
   
// Initialize our plugin object.

// global $tibdit_plug;
// if (class_exists("tibditWidget") && !$tibdit_plug) 
//   {
//   	tiblog("tibdit_plug");
//     $tibdit_plug = new tibditWidget();  
//   } 

	function tiblog($message)
		{
			// error_log(date("d H:i:s",time())." - ".$message."\n", 3, "/var/log/lighttpd/tibdit.log");
		}

?>
<?PHP 

 // tibdit plugin settings
 // Version: 1.3.1
 // License: GPL3


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
if( !defined( 'TIBDIT_DIR')) define( 'TIBDIT_DIR', plugin_dir_path( __FILE__ ) );
if( !defined( 'TIBDIT_URL')) define( 'TIBDIT_URL', plugin_dir_url( __FILE__ ) );

// use LinusU\Bitcoin\AddressValidator;
// use AddressValidator;
// include 'AddressValidator.php';
 
tiblog("admin page");

if (!function_exists('is_admin')) 
{
  tiblog("admin but not admin");
  header('Status: 403 Forbidden');
  header('HTTP/1.1 403 Forbidden');
  exit();
}

if (!class_exists("tibdit_settings")) 
{
  class tibdit_settings
  {
    public static $default_settings = array
    (  
      'title' => 'tibdit',
      'intro' => 'Please drop a microdonation in my tibjar',
      'acktime' => 3,
      'colour' => '#806020'
    );

    private $options; 

    private $help_hook;

    function __construct() 
    { 
      tiblog("||ADM __construct");

      $this->page_id = 'tibdit_options';
      // This is the get_options slug used in the database to store our plugin option values.
      $this->settings_field = 'tibdit_options';
      $this->options = get_option( $this->settings_field );
      $this->page_title = "tibdit plugin settings";
      $this->section = "tibdit_main_section";
      $this->list = "tibdit_tibs_list";
      $this->blockchain = "tibdit_blockchain";

      add_action( 'admin_menu', array($this, 'add_admin_menu') );
      add_action( 'admin_init', array($this, 'init_admin_page') );
      add_action( 'admin_enqueue_scripts', array($this,'tibdit_settings_enqueue') );
      // add_action( 'admin_enqueue_scripts', array($this, 'mw_enqueue_color_picker') );
      add_filter( 'contextual_help', array($this, 'admin_help'), 10, 3);
      $plugin = plugin_basename(__FILE__); 
      tiblog("plugin_action_links_$plugin");
      add_filter( "plugin_action_links_tibdit/tibdit.php", array($this,'bd_plugins_page'));
    }

  // Add settings link on plugin page
  function bd_plugins_page($links) 
    { 
      tiblog("bd_plugins_page() " . var_dump($inks));
      $settings_link = '<a href="options-general.php?page=tibdit_options#help">Settings &amp; Help</a>'; 
      array_unshift($links, $settings_link); 
      return $links; 
    }

  function init_admin_page()
    {
      add_option( $this->settings_field, tibdit_settings::$default_settings );

      $this->options = get_option( $this->settings_field );
      $this->options = wp_parse_args($this->options, tibdit_settings::$default_settings);

      tiblog( "||ADM init: " . var_export($this->options, true));

      register_setting( $this->settings_field, $this->settings_field, array($this, 'sanitise') );
      
      add_settings_section($this->section, '', array($this, 'main_section'), $this->page_id);

      // add_settings_field('title', 'Widget Heading', array($this, 'title_field'), $this->page_id, $this->section);
      // add_settings_field('intro', 'Widget Intro', array($this, 'intro_field'), $this->page_id, $this->section);
      add_settings_field('payaddr', 'Bitcoin Address', array($this, 'payaddr_field'), $this->page_id, $this->section);
      add_settings_field('acktime', 'Acknowledge tib for', array($this, 'acktime_field'), $this->page_id, $this->section);
      // add_settings_field('widget_colour', 'Widget background shading', array($this, 'widget_colour'), $this->page_id, $this->section);

      if (get_option('tib_list'))
      {
        add_settings_section($this->list, "list", array($this, 'list_section'), $this->page_id);
        update_option('tib_list', false);
      }
    }

    function admin_help($contextual_help, $screen_id, $screen) 
    {
      tiblog("admin_help() ");
      include ('tibdit-settings-help.php');

      if ($screen_id == $this->help_hook) {
         // $contextual_help = 'This is where I would provide help to the user on how everything in my admin panel works. Formatted HTML works fine in here too.';
          $screen->add_help_tab( array( 
            'id' => "bd_help_overview",            //unique id for the tab
            'title' => "overview",      //unique visible title for the tab
            'content' => $bd_help_overview,  //actual help text
            'callback' => $callback //optional function to callback
          ) );
          $screen->add_help_tab( array( 
            'id' => "bd_help_settings",            //unique id for the tab
            'title' => "settings",      //unique visible title for the tab
            'content' => $bd_help_settings,  //actual help text
            'callback' => $callback //optional function to callback
          ) );
          $screen->add_help_tab( array( 
           'id' => "bd_help_bitcoin",            //unique id for the tab
           'title' => "bitcoin",      //unique visible title for the tab
           'content' => $bd_help_bitcoin,  //actual help text
           'callback' => $callback //optional function to callback
          ) );
          $screen->add_help_tab( array( 
            'id' => "bd_help_shortcodes",            //unique id for the tab
            'title' => "shortcodes",      //unique visible title for the tab
            'content' => $bd_help_shortcodes,  //actual help text
            'callback' => $callback //optional function to callback
          ) );
          $screen->add_help_tab( array( 
            'id' => "bd_help_widgets",            //unique id for the tab
            'title' => "widgets",      //unique visible title for the tab
            'content' => $bd_help_widgets,  //actual help text
            'callback' => $callback //optional function to callback
          ) );        
          $screen->add_help_tab( array( 
            'id' => "bd_help_testmode",            //unique id for the tab
            'title' => "testmode",      //unique visible title for the tab
            'content' => $bd_help_testmode,  //actual help text
            'callback' => $callback //optional function to callback
            ) );
      }
      return $contextual_help;
    }

    function tibdit_settings_enqueue()
    {
      $plugurl = plugin_dir_url( __FILE__ );
      tiblog("||ADM enqueue");

      // wp_enqueue_script( 'base58_library', $plugurl.'/base58.js' );
      wp_enqueue_script( 'Tom_Wu_jsbn.js', $plugurl.'jsbn.js' );
      wp_enqueue_script( 'Tom_Wu_jsbn2.js', $plugurl.'jsbn2.js' );
      wp_enqueue_script( 'sha256', $plugurl.'crypto-sha256.js');
      wp_enqueue_script( 'tibdit_plugin_settings', $plugurl.'tibdit-settings.js', array(), "13" );
      wp_enqueue_script( 'btc_payaddr_validator', $plugurl.'btcaddr_validator.js' );
      wp_enqueue_style( 'tibdit_plugin', $plugurl.'tibbee.css', array(), "13");

      wp_enqueue_style( 'wp-color-picker' );
      wp_enqueue_script( 'wp-color-picker' );
      wp_enqueue_script( 'bd-admin-bottom', $plugurl.'tibdit-settings-bottom.js', array( 'wp-color-picker' ), "2", true );
    }


    function main_section()
    { 
      $plugurl = plugin_dir_url( __FILE__ );

      tiblog("||ADM form section"); 

      echo '<br>Please refer to the <a class="bd-admin-link" onclick="jQuery(\'a#contextual-help-link\').trigger(\'click\');"> plugin help</a> for information and instructions.';

    }

    function list_section()
    {
      tiblog("||ADM list section");
      // $qargs = array()
      
      echo("<table class='widefat'><tr><th>title</th><th>id</th><th>tibs received</th></tr>");
      
      $alloptions = wp_load_alloptions();

      // tiblog("admin: list section: list all options: " . var_export($alloptions, true));

      foreach ($alloptions as $wp_option => $wp_option_value)
      {
        tiblog("admin: list section: list all options: " . substr($wp_option, 0, 10) );

        if($wp_option == "WP_SITE" or substr($wp_option, 0, 10) == "tib_count_")
        {
          echo("<tr><td>");
          echo( $wp_option);
          echo("</td><td>n/a</td><td>");
          echo($wp_option_value);
          echo("</td></tr>"); 
        }
      }

      $qry = new WP_Query( 'meta_key=tib_count');
      if ($qry -> have_posts())
      {

        while ($qry -> have_posts()) 
        { 
          $qry -> the_post();
          $tibcountfield = get_post_custom_values("tib_count");
          // echo("test1" . var_export($tibcountfield, true));
          echo("<tr><td>");
          echo( the_title());
          echo( "</td><td>" . get_the_ID() . "</td><td>" . $tibcountfield[0] . "</td></tr>");
        }
      }
        echo("</table><br><br>");
    }

    // function title_field( $args )
    // {
    //   $slug= "title";
    //   $value= $this->options[$slug];

    //   echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'
    //     class='bd' type='text' size=20 maxlength=20 onchange='lowercase_tib(this);' 
    //     onkeypress='this.onchange();' onpaste='this.onchange();' oninput='this.onchange();'  >";
    //   echo "<span id='title_field_status'></span>";
    // }


    // function intro_field( $args )
    // {
    //   $slug= "intro";
    //   $value= $this->options[$slug];


    // echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'";
    // echo "class='bd' type='text' size=100 maxlength=100 onchange='bd_lowercase_tib(this);'
    //    onkeypress='this.onchange();' onpaste='this.onchange();' oninput='this.onchange();'  >";
    // echo "<span id='intro_field_status'></span>";
    // }



    function payaddr_field( $args )
    {
      $slug= "payaddr";
      $value= $this->options[$slug];

      $plugurl= plugin_dir_url( __FILE__ );

      echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'
        class='bd' type='text' size=36 maxlength=36 onchange='bd_payaddr_change(this, \"$plugurl\");' 
        onkeypress='this.onchange();' onpaste='this.onchange();' oninput='this.onchange();'  >"; 

      echo "<span class='bd status' id='payaddr_field_status'>&emsp;?</span>";

    }


    function acktime_field( $args )
    {
      $slug="acktime";
      $value=$this->options[$slug];

      echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'
        class='bd' type='number' min='1' max='30' step='1'  >"; 

      echo "&emsp;days &emsp;(or minutes if a testmode / testnet address)";
    }

    function widget_colour( $args )
    {
      $slug="widget_colour";
      $value=$this->options[$slug];

      echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'
        class='bd bd-colourp' type='text' data-default-color='$value' >"; 


    }


    function add_admin_menu() 
    {
      tiblog("add admin menu");
      if ( ! current_user_can('manage_options') )
        return;

      // $this->pagehook = $page =  add_options_page( $this->page_title, 'tibdit', 'manage_options', $this->page_id, array($this,'render') );
      $this->help_hook = add_options_page( $this->page_title, 'tibdit', 'manage_options', $this->page_id, array($this,'render') );
    }
     

    function sanitise($opts_in) // Sanitize our plugin settings  array as needed.
    {
      tiblog("||ADM sanitise: POST " . var_export($_POST, true));

      $new_options=array();
     
      if (isset($_POST['list']))
      {
        tiblog( "||ADM sanitise: list !!!!");
        update_option( 'tib_list', true);   //persist request for list of tibs through page multiple refreshes
      }

      // if( isset($opts_in['title']))
      //   $new_options['title']= sanitize_text_field($opts_in['title']);
      // else
        $new_options['title']= tibdit_settings::$default_settings['title'];

      // if( isset($opts_in['intro']))
      //   $new_options['intro']= sanitize_text_field($opts_in['intro']);
      // else
        $new_options['intro']= tibdit_settings::$default_settings['intro'];

      if( isset($opts_in['payaddr']) && strlen($opts_in['payaddr']) > 2)
        if (AddressValidator::typeOf($opts_in['payaddr']))
          $new_options['payaddr'] = $opts_in['payaddr'];
        else
          $new_options['payaddr'] = "";
      else
        $new_options['payaddr'] = "";

      if( isset($opts_in['acktime']))
        if (intval($opts_in['acktime']) > 0 && intval($opts_in['acktime']) < 31)
          $new_options['acktime'] = intval($opts_in['acktime']);
        else
          $new_options['acktime']= tibdit_settings::$default_settings['acktime'];
      else
        $new_options['acktime']= tibdit_settings::$default_settings['acktime'];

      // if( isset($opts_in['widget_colour']))
      //   $new_options['widget_colour'] = ($opts_in['widget_colour']);
      // else
      //   $new_options['widget_colour']= tibdit_settings::$default_settings['widget_colour'];
      
      tiblog("||ADM sanitise: options " . var_export($opts_in, true));
      tiblog("||ADM sanitise: new_options " . var_export($new_options, true));
      
      return $new_options;      
    }

      
    function render() 
    {
      // if (! current_user_can('manage_options'))
      //   wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
      // $mode="";
      if( substr($this->options['payaddr'],0,1) == 'm' or substr($this->options['payaddr'],0,1) == 'n' )
      {
        $mode="/testnet";
      }
      $plugurl = plugin_dir_url( __FILE__ );
      tiblog("admin render");
      echo "<div class='wrap'>";
      echo "<h2>$this->page_title</h2>";
      echo "<form method='post' action='options.php' class='bd';>";
      settings_fields( $this->settings_field );  
      do_settings_sections( $this->page_id );
      submit_button( 'Save Changes', 'primary', 'submit', false); 
      echo("&emsp;");
      submit_button( 'list tib counts', 'secondary', 'list', false, array( 'onclick' => "{}" ));
      submit_button( 'balance', 'secondary', 'blockchain', false, array( 'onclick' => "{biteasy_blockchain();}"));
      
      echo "<script>payaddr.onchange();</script>";
      echo "</form></div>";
  


    }
  } // end class
} // end if
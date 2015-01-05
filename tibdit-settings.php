<?PHP 

 // tibdit plugin settings
 // Version: 1.2.30
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
define( 'TIBDIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'TIBDIT_URL', plugin_dir_url( __FILE__ ) );

use LinusU\Bitcoin\AddressValidator;
 
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
      'acktime' => 3
    );

    private $options; 

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

      add_action( 'admin_menu', array($this, 'add_admin_menu') );
      add_action( 'admin_init', array($this, 'init_admin_page') );
      add_action( 'admin_enqueue_scripts', array($this,'tibdit_settings_enqueue') );
    }


    function init_admin_page()
    {
      add_option( $this->settings_field, tibdit_settings::$default_settings );

      $this->options = get_option( $this->settings_field );
      $this->options = wp_parse_args($this->options, tibdit_settings::$default_settings);

      tiblog( "||ADM init: " . var_export($this->options, true));

      register_setting( $this->settings_field, $this->settings_field, array($this, 'sanitise') );
      
      add_settings_section($this->section, '', array($this, 'main_section'), $this->page_id);

      add_settings_field('title', 'Widget Heading', array($this, 'title_field'), $this->page_id, $this->section);
      add_settings_field('intro', 'Widget Intro', array($this, 'intro_field'), $this->page_id, $this->section);
      add_settings_field('payaddr', 'Bitcoin Address', array($this, 'payaddr_field'), $this->page_id, $this->section);
      add_settings_field('acktime', 'Acknowledge tib for', array($this, 'acktime_field'), $this->page_id, $this->section);

      if (get_option('tib_list'))
      {
        add_settings_section($this->list, "list", array($this, 'list_section'), $this->page_id);
        update_option('tib_list', false);
      }
    }


    function tibdit_settings_enqueue()
    {
      $plugurl = plugin_dir_url( __FILE__ );
      tiblog("||ADM enqueue");

      // wp_enqueue_script( 'base58_library', $plugurl.'/base58.js' );
      wp_enqueue_script( 'Tom_Wu_jsbn.js', $plugurl.'jsbn.js' );
      wp_enqueue_script( 'Tom_Wu_jsbn2.js', $plugurl.'jsbn2.js' );
      wp_enqueue_script( 'sha256', $plugurl.'crypto-sha256.js');
      wp_enqueue_script( 'tibdit_plugin_settings', $plugurl.'tibdit-settings.js' );
      wp_enqueue_script( 'btc_payaddr_validator', $plugurl.'btcaddr_validator.js' );
      wp_enqueue_style( 'tibdit_plugin', $plugurl.'/tibbee.css' );
    }


    function main_section()
    { 
      $plugurl = plugin_dir_url( __FILE__ );

      tiblog("||ADM form section"); 

      echo
<<<INSTRUCTIONS
      <h4><u>bitcoin</u></h4>
      <p>To collect tibs you need a bitcoin address.  You have many options available If you do not already have an address you wish to use.  One 
      very fast and very simple way is to go to <a href=https://bitaddress.org style="font-family: monospace;" target='_tibdit'>bitaddress.org</a> and follow the instructions 
      there.  While not as secure as more complex options, this is suitable if you want to get set up and do not expect to receive a large 
      amount of money in a short timeframe.  If you are UK/EU based and would rather convert your bitcoin easily into GBP/EUR, then you may 
      want to try <a href=https://cryptopay.me/ style="font-family: monospace;" target='_tibdit'>Cryptopay</a>.  If you want a secure online bitcoin wallet to accumulate bitcoin, 
      then we suggest <a style="font-family: monospace;" href=https://blockchain.info/wallet>Blockchain.info</a>.</p>
      <p>Regardless of how you obtain your a bitcoin address, it is very important that you keep the secret private key safe and secure.  For 
      example, you might want to print it twice and put the copies in different places. If you lose your private key, you will have lost any 
      bitcoin at the corresponding address. If someone else gets hold of your key, they can easily steal your bitcoin!  You only need your private key when you want to
      transfer or spend your bitcoin.</p>
      <p>Enter your public bitcoin address below, not your Private Key.  A green tick will show when you have entered a valid bitcoin address.</p>
      <h4><u>shortcodes</u></h4>
      <p>Use<span style="font-family: monospace;"> [tib_post] </span>to place a tib button specific to individual posts.  The tib counter 
      and subref will be specific to the WordPress post ID.&emsp;
      Use<span style="font-family: monospace;"> [tib_site] </span> to place a 'site wide' tib button that will use the same counter and 
      subref wherever it appears. &emsp;You can also override settings for individual shortcodes.  
      For example<span style="font-family: monospace;"> [tib_post payaddr="bitcoinaddress"]  </span> or<span style="font-family: monospace;"> [tib_site title="sometext"]  </span>.
      <p>By enclosing part of a post in shortcode tags like this:&emsp;<span style="font-family: monospace;"> <i>beginning-of-post...</i> [tib_post] <i>...rest-of-post...</i> [/tib_post] </span>
      the rest-of-post part will be shown only after the reader had paid a tib. 
      <h4><u>widget areas</u></h4>
      <p>You can also use the Widgets settings under the Appearance menu.  tib widgets placed from there can have different settings,
      including for the bitcoin address, but will default to the settings on this page if left blank.  It is recommended to always set a 
      subref for widgets placed using the Widget Appearance settings.</p>
INSTRUCTIONS;
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

    function title_field( $args )
    {
      $slug= "title";
      $value= $this->options[$slug];

      echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'
        class='bd' type='text' size=20 maxlength=20 onchange='lowercase_tib(this);' 
        onkeypress='this.onchange();' onpaste='this.onchange();' oninput='this.onchange();'  >";
      echo "<span id='title_field_status'></span>";
    }


    function intro_field( $args )
    {
      $slug= "intro";
      $value= $this->options[$slug];

      echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'";
      echo "class='bd' type='text' size=100 maxlength=100 onchange='lowercase_tib(this);'
         onkeypress='this.onchange();' onpaste='this.onchange();' oninput='this.onchange();'  >";
      echo "<span id='intro_field_status'></span>";
    }


    function payaddr_field( $args )
    {
      $slug= "payaddr";
      $value= $this->options[$slug];

      $plugurl= plugin_dir_url( __FILE__ );

      echo "<input id='$slug' name='$this->settings_field[$slug]' value='$value'
        class='bd' type='text' size=36 maxlength=36 onchange='payaddr_change(this, \"$plugurl\");' 
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


    function add_admin_menu() 
    {
      tiblog("add admin menu");
      if ( ! current_user_can('manage_options') )
        return;

      // $this->pagehook = $page =  add_options_page( $this->page_title, 'tibdit', 'manage_options', $this->page_id, array($this,'render') );
      add_options_page( $this->page_title, 'tibdit', 'manage_options', $this->page_id, array($this,'render') );
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

      if( isset($opts_in['title']))
        $new_options['title']= sanitize_text_field($opts_in['title']);
      else
        $new_options['title']= tibdit_settings::$default_settings['title'];

      if( isset($opts_in['intro']))
        $new_options['intro']= sanitize_text_field($opts_in['intro']);
      else
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

      tiblog("||ADM sanitise: options " . var_export($opts_in, true));
      tiblog("||ADM sanitise: new_options " . var_export($new_options, true));
      
      return $new_options;      
    }

      
    function render() 
    {
      // if (! current_user_can('manage_options'))
      //   wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
      $plugurl = plugin_dir_url( __FILE__ );
      tiblog("admin render");
      echo "<div class='wrap'><h2>$this->page_title       <img class='beta' src='$plugurl/beta-35px.png' alt='beta'/></h2>";
      echo "<form method='post' action='options.php' class='bd';>";
      settings_fields( $this->settings_field );  
      do_settings_sections( $this->page_id );
      submit_button( 'Save Changes', 'primary', 'submit', false); 
      echo("&emsp;");
      submit_button( 'list tib counts', 'secondary', 'list', false, array( 'onclick' => "{}" ));
      echo 
<<<TESTMODE
        <h4><u>tibdit testmode</u>&emsp;<img src='$plugurl/testmode-icon-24px.png' style='width: 1.3em; vertical-align: middle'></h4>
        <p>Bitcoin addresses that start with <span style="font-family: monospace;">'m'</span> or <span style="font-family: monospace;">'n'</span> are 'testnet' addresses that can be used readily with no actual money or value involved.  
        tibdit will detect a testnet address and trigger testmode, which allows anyone to experiment with tibbing at no risk.  Testmode is indicated with the yellow beaker icon. Conversely,
        Bitcoin addresses that start with a <span style="font-family: monospace;">'1'</span> are production, or 'mainnet' addresses; users need to have purchased a bundle of real tibs 
        to tib 'mainnet' addresses.  The bitcoin testnet and mainnet are completely seperate, there is no risk of spending real bitcoins on
        the testnet, or the reverse.</p>
        <p>You can generate you own testnet bitcoin address in just a few seconds at 
        <a style="font-family: monospace;" href="https://www.bitaddress.org/bitaddress.org-v2.9.3-SHA1-7d47ab312789b7b3c1792e4abdb8f2d95b726d64.html?testnet=true">bitaddress testnet edition</a>.</p>
TESTMODE;
      echo "<script>payaddr.onchange();</script>";
      echo "</form></div>";
    }
  } // end class
} // end if
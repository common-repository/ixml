<?php
/*
Plugin Name: iXML - Google XML sitemap generator
Plugin URI: http://www.icount.co.il/off-topic/plugins
Description: Generate XML sitemaps, with easy exclusions. Using a simple "exclude" checkbox to your posts and pages admin pages you can control exactly what will appear in your Google Sitemap.
Version: 0.6
Author: iCount
Author URI: http://www.icount.co.il
License: GPLv2 or later
*/

//============================================ Function for adding style ====================
if( ! function_exists( 'iXML_add_my_stylesheet' ) ) {
	function iXML_add_my_stylesheet() {
		wp_register_style( 'iXML-StyleSheets', plugins_url( 'css/css.css', __FILE__ ) );
		wp_enqueue_style( 'iXML-StyleSheets' );
	}
}

//============================================ Function for adding page in admin menu ====================
if( ! function_exists( 'iCount_add_menu_render' ) ) {
	function iCount_add_menu_render() {
		global $title;
		$active_plugins = get_option('active_plugins');
		$all_plugins		= get_plugins();

		$array_activate = array();
		$array_install	= array();
		$array_recomend = array();
		$count_activate = $count_install = $count_recomend = 0;
		$array_plugins = array();
		foreach($array_plugins as $plugins) {
			if( 0 < count( preg_grep( "/".$plugins[0]."/", $active_plugins ) ) ) {
				$array_activate[$count_activate]['title'] = $plugins[1];
				$array_activate[$count_activate]['link']	= $plugins[2];
				$array_activate[$count_activate]['href']	= $plugins[3];
				$array_activate[$count_activate]['url']	= $plugins[5];
				$count_activate++;
			}
			else if( array_key_exists(str_replace("\\", "", $plugins[0]), $all_plugins) ) {
				$array_install[$count_install]['title'] = $plugins[1];
				$array_install[$count_install]['link']	= $plugins[2];
				$array_install[$count_install]['href']	= $plugins[3];
				$count_install++;
			}
			else {
				$array_recomend[$count_recomend]['title'] = $plugins[1];
				$array_recomend[$count_recomend]['link']	= $plugins[2];
				$array_recomend[$count_recomend]['href']	= $plugins[3];
				$array_recomend[$count_recomend]['slug']	= $plugins[4];
				$count_recomend++;
			}
		}
		?>
		<div class="wrap">
			<div class="icon32 icon32-iCount" id="icon-options-general"></div>
			<h2><?php echo $title;?></h2>
			<?php if( 0 < $count_activate ) { ?>
			<div>
				<h3><?php _e( 'Activated plugins', 'sitemap' ); ?></h3>
				<?php foreach( $array_activate as $activate_plugin ) { ?>
				<div style="float:left; width:200px;"><?php echo $activate_plugin['title']; ?></div> <p><a href="<?php echo $activate_plugin['link']; ?>" target="_blank"><?php echo __( "Read more", 'sitemap'); ?></a> <a href="<?php echo $activate_plugin['url']; ?>"><?php echo __( "Settings", 'sitemap'); ?></a></p>
				<?php } ?>
			</div>
			<?php } ?>
			<?php if( 0 < $count_install ) { ?>
			<div>
				<h3><?php _e( 'Installed plugins', 'sitemap' ); ?></h3>
				<?php foreach($array_install as $install_plugin) { ?>
				<div style="float:left; width:200px;"><?php echo $install_plugin['title']; ?></div> <p><a href="<?php echo $install_plugin['link']; ?>" target="_blank"><?php echo __( "Read more", 'sitemap'); ?></a></p>
				<?php } ?>
			</div>
			<?php } ?>
		</div>
		<?php
	}
}

//============================================ Function for adding menu and submenu ====================
if( ! function_exists( 'iXML_add_pages' ) ) {
	function iXML_add_pages() {
		/*
		add_menu_page( __( 'iCount Plugins', 'sitemap' ), __( 'iCount Plugins', 'sitemap' ), 'manage_options', 'iCount_plugins', 'iCount_add_menu_render', WP_CONTENT_URL."/plugins/iXML/images/px.png", 1001); 
		add_submenu_page( 'iCount_plugins', __( 'iXML Options', 'sitemap' ), __( 'iXML', 'sitemap' ), 'manage_options', "iXML.php", 'iXML_settings_page');
		
		global $url_home;
		global $url;
		global $url_send;
		global $url_send_sitemap;
		$url_home = home_url();
		$url = urlencode( $url_home . "/" );
		$url_send = "https://www.google.com/webmasters/tools/feeds/sites/";
		$url_send_sitemap = "https://www.google.com/webmasters/tools/feeds/";
		*/
		add_options_page('iXML Options Page', 'iXML', 'manage_options', __FILE__, 'iXML_settings_page');
	}
}

//============================================ Function for creating sitemap file ====================
if( ! function_exists( 'iXML_create_sitemap' ) ) {
	function iXML_create_sitemap() {
		global $wpdb, $iXML_settings; 
		$str = "";
		foreach( $iXML_settings as $val ) {
			if( $str != "")
				$str .= ", ";
			$str .= "'".$val."'";
		}
		$loc = $wpdb->get_results( "SELECT ID, post_modified, post_status, post_type, ping_status FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN (" . $str . ") AND iXML_exclude != 1" );
		$xml = new DomDocument('1.0','utf-8');
		$xml_stylesheet_path = "wp-content/plugins/iXML/sitemap.xsl";
		$xslt = $xml->createProcessingInstruction( 'xml-stylesheet', "type=\"text/xsl\" href=\"$xml_stylesheet_path\"" );
		$xml->appendChild($xslt);
		$urlset = $xml->appendChild( $xml->createElementNS( 'http://www.sitemaps.org/schemas/sitemap/0.9','urlset' ) );
		foreach( $loc as $val ) {
			$url = $urlset->appendChild( $xml->createElement( 'url' ) );
			$loc = $url->appendChild( $xml->createElement( 'loc' ) );
			$permalink = get_permalink( $val->ID );
			$loc->appendChild( $xml->createTextNode( $permalink ) );
			$lastmod = $url->appendChild( $xml->createElement( 'lastmod' ) );
			$now = $val->post_modified;
			$date = date( 'Y-m-d\TH:i:sP', strtotime( $now ) );
			$lastmod->appendChild( $xml -> createTextNode( $date ) );
			$changefreq = $url -> appendChild( $xml->createElement( 'changefreq' ) );
			$changefreq->appendChild( $xml->createTextNode( 'monthly' ) );
			$priority = $url->appendChild( $xml->createElement( 'priority' ) );
			$priority->appendChild( $xml->createTextNode( 1.0 ) );
		}
		$xml->formatOutput = true;
		$xml->save( ABSPATH . 'sitemap.xml' );		
	}
}

if( ! function_exists( 'register_iXML_settings' ) ) {
	function register_iXML_settings() {
		global $wpmu, $iXML_settings;

		$iXML_option_defaults = array( 'page', 'post' );

		if ( 1 == $wpmu ) {
			if( ! get_site_option( 'iXML_settings' ) ) {
				add_site_option( 'iXML_settings', $iXML_option_defaults );
			}
		} 
		else {
			if( ! get_option( 'iXML_settings' ) )
				add_option( 'iXML_settings', $iXML_option_defaults );
		}
			
		if ( 1 == $wpmu )
			$iXML_settings = get_site_option( 'iXML_settings' ); 
		else
			$iXML_settings = get_option( 'iXML_settings' );
	}	
}

if( ! function_exists( 'delete_iXML_settings' ) ) {
	function delete_iXML_settings() {
		delete_option( 'iXML_settings' );
	}
}   

if( ! function_exists( 'iXML_settings_global' ) ) {
	function iXML_settings_global() {
		global $wpmu, $iXML_settings;
		$iXML_option_defaults = array( 'page', 'post' );
		$iXML_settings = array();
		if ( 1 == $wpmu )
			$iXML_settings = get_site_option( 'iXML_settings' ); 
		else
			$iXML_settings = get_option( 'iXML_settings' );
		$iXML_settings = @array_merge( $iXML_option_defaults, $iXML_settings );
	}
}   



//============================================ Function for creating setting page ====================
if ( !function_exists ( 'iXML_settings_page' ) ) {
	function iXML_settings_page () {
		global $url_home, $iXML_settings, $url, $wpdb;
		$url_robot = ABSPATH . "robots.txt";
		$url_sitemap = ABSPATH . "sitemap.xml";
		$message = "";
		if( isset( $_POST['iXML_new'] ) && check_admin_referer( plugin_basename(__FILE__), 'iXML_nonce_name' ) ) {
			$message =  __( "Your sitemap file was created in the root directory of the site. ", 'sitemap' );
			iXML_create_sitemap();
		}
		if( isset( $_REQUEST['iXML_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'iXML_nonce_name' ) ) {
			$iXML_settings = isset( $_REQUEST['iXML_settings'] ) ? $_REQUEST['iXML_settings'] : array() ;
			update_option( 'iXML_settings', $iXML_settings );
			$message .= __( "Options saved." , 'sitemap' );	
		}
		$iXML_result = $wpdb->get_results( "SELECT post_type FROM ". $wpdb->posts ." WHERE post_type NOT IN ( 'revision', 'attachment', 'nav_menu_item' ) GROUP BY post_type" );	
		?>
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2><?php _e( "iXML options", 'sitemap' ); ?></h2>
			<div class="updated fade" <?php if( ! isset( $_REQUEST['iXML_new'] ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<form action="/wp-admin/options-general.php?page=ixml/iXML.php" method='post' id="iXML_auth" name="iXML_auth">
				<?php //=============================== Creating sitemap file ====================================
				if( file_exists( $url_sitemap ) ) {
					echo "<p>". __( "The sitemap file already exists. If you want to change it with a new sitemap file, check the necessary box below.", 'sitemap' ) . "</p>";
				}
				else {
					iXML_create_sitemap();
					echo "<p>".__( "Your sitemap file was created in the root directory of the site. ", 'sitemap' ) . "</p>";	
				}
				//========================================== Recreating sitemap file ====================================				
				echo '<p>'. __( "If you don't want to add this file automatically you may go through", 'sitemap' ) . " <a href=\"https://www.google.com/webmasters/tools/home?hl=en\">". __( "this", 'sitemap' ) . "</a> ". __( "link, sign in, select necessary site, select 'Sitemaps' and type in necessary field", 'sitemap' ) ." - '". $url_home."/sitemap.xml'.</p>";
				if ( ! function_exists( 'curl_init' ) ) {
					echo '<p class="error">'. __( "This hosting doesn't support CURL, so you can't add sitemap file automatically", 'sitemap' ). "</p>";	
					$curl_exist = 0;
				}
				else {
					$curl_exist = 1;
				}?>
				<table class="form-table">
					<tr valign="top">
						<td colspan="2">
							<input type='checkbox' name='iXML_new' value="1" /> <label for="iXML_new"><?php _e( "I want to create new sitemap file", 'sitemap' );	?></label>
						</td>
					</tr>
					<tr valign="top">
						<td colspan="2">
							<input type='checkbox' name='iXML_checkbox' value="1" /> <label for="iXML_checkbox"><?php _e( "I want to add sitemap file path in robots.txt", 'sitemap' );?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" colspan="2"><?php _e( 'Please choose the necessary post types in order to add the links to them in the sitemap:', 'sitemap' ); ?> </th>
					</tr>
					<tr valign="top">
						<td colspan="2">
							<?php 
							foreach ( $iXML_result as $key => $value ) { ?>
								<input type="checkbox" <?php echo ( in_array( $value->post_type, $iXML_settings ) ?  'checked="checked"' : "" ); ?> name="iXML_settings[]" value="<?php echo $value->post_type; ?>"/><span style="text-transform: capitalize; padding-left: 5px;"><?php echo $value->post_type; ?></span><br />
							<?php } ?>
						</td>
					</tr>	
					<?php if ( $curl_exist == 1 ) { ?>
					<tr valign="top">
						<td colspan="2">
							<?php echo __( "Type here your login and password from google webmaster tools account to add or delete site and sitemap file automatically or to get information about this site in google webmaster tools.", 'sitemap' ); ?> 
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Settings for remote work with google webmaster tools', 'sitemap' ); ?></th>
						<td>
							<input type='text' name='iXML_email' value="<?php if( isset( $_REQUEST['iXML_email'] ) ) echo  $_REQUEST['iXML_email']; ?>" /> <label for='iXML_email'><?php _e( "Login", 'sitemap' );	?></label><br />
							<input type='password' name='iXML_passwd' value="<?php if( isset( $_REQUEST['iXML_email'] ) ) echo  $_REQUEST['iXML_email']; ?>" /> <label for='iXML_passwd'><?php _e( "Password", 'sitemap' );	?></label><br />
							<input type='radio' name='iXML_menu' value="ad" /> <label for='iXML_menu'><?php _e( "I want to add this site to the google webmaster tools", 'sitemap' );	?></label><br />
							<input type='radio' name='iXML_menu' value="del" /> <label for='iXML_menu'><?php _e( "I want to delete this site from google webmaster tools", 'sitemap' ); ?></label><br />
							<input type='radio' name='iXML_menu' value="inf" /> <label for='iXML_menu'><?php _e( "I want to get info about this site in google webmaster tools", 'sitemap' );	?></label>
						</td>
					</tr>
					<?php } ?>
				</table>
				<input type="hidden" name="iXML_submit" value="submit" />
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
				<?php wp_nonce_field( plugin_basename(__FILE__), 'iXML_nonce_name' ); ?>
			</form>
		</div>
		<?php
		//============================ Adding location of sitemap file to the robots.txt =============
		if( isset( $_POST['iXML_checkbox'] ) && check_admin_referer( plugin_basename(__FILE__), 'iXML_nonce_name' ) ){
			if ( file_exists( $url_robot ) ) {		
				$fp = fopen( ABSPATH . 'robots.txt', "a+" );
				$flag = false;
				while ( ($line = fgets($fp)) !== false) {
						if ( $line == "Sitemap: " . $url_home . "/sitemap.xml\n" )
								$flag = true;
				}
				if( ! $flag )
						fwrite($fp, "\nSitemap: " . $url_home . "/sitemap.xml\n" );
				fclose ( $fp ); 
			}
			else{
				$fp = fopen( ABSPATH . 'robots.txt', "a+" );
				fwrite( $fp, "# User-agent: *\n
# Disallow: /wp-admin/\n 
# Disallow: /wp-includes/\n
# Disallow: /wp-trackback\n
# Disallow: /wp-feed\n
# Disallow: /wp-comments\n
# Disallow: /wp-content/plugins\n
# Disallow: /wp-content/themes\n
# Disallow: /wp-login.php\n
# Disallow: /wp-register.php\n
# Disallow: /feed\n
# Disallow: /trackback\n
# Disallow: /cgi-bin\n
# Disallow: /comments\n
# Disallow: *?s=
\nSitemap: " . $url_home . "/sitemap.xml" );
				fclose ($fp);
			}
		}
		//================================ Different checks for the valid entering data ===================
		if( isset( $_POST['iXML_menu'] ) && ( ! isset( $_POST['iXML_email'] ) || ! isset( $_POST['iXML_passwd'] ) || empty( $_POST['iXML_email'] ) || empty( $_POST['iXML_passwd'] ) ) ) { ?> 
			<script type = "text/javascript"> alert( "<?php _e( 'You must enter login and password', 'sitemap' );	?>" ) </script>
		<?php }
		else if( isset( $_POST['iXML_email'] ) && isset( $_POST['iXML_passwd'] ) && isset( $_POST['iXML_menu'] ) && $_POST['iXML_menu'] != "ad" && $_POST['iXML_menu'] != "del" && $_POST['iXML_menu'] != "inf" ) { ?>
			<script type = "text/javascript"> alert( "<?php _e( 'You must choose at least one action', 'sitemap' );	?>" ) </script>
		<?php }
		else if( isset( $_POST['iXML_email'] ) && isset( $_POST['iXML_passwd'] ) && isset( $_POST['iXML_menu'] ) && ! empty( $_POST['iXML_email'] ) && ! empty( $_POST['iXML_passwd'] )) {	
			// =================== Connecting to the google account =================
			$data = array( 'accountType' => 'GOOGLE',
				'Email' => $_POST['iXML_email'],
				'Passwd' => $_POST['iXML_passwd'],
				'source' =>'PHI-cUrl-Example',
				'service' =>'sitemaps'
			);  
			$ch = curl_init();    
			curl_setopt( $ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin" );	
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );  
			curl_setopt( $ch, CURLOPT_POST, true );  
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );  
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );  
			curl_setopt( $ch,  CURLOPT_UNRESTRICTED_AUTH, true ); 
			$hasil = curl_exec( $ch );
			curl_close( $ch );
			$httpResponseAr = explode( "\n", $hasil );
			$httpParsedResponseAr = array();
			foreach ( $httpResponseAr as $i => $rVal ) {
				if( strpos( $rVal, "=" ) !== false ) {
					list( $qKey, $qVal ) = explode ( "=", $rVal );
					$httpParsedResponseAr[$qKey] = $qVal;
				}
			}
			$au = isset( $httpParsedResponseAr["Auth"] ) ? $httpParsedResponseAr["Auth"] : false;
			if ( ! $au && ( $_POST['iXML_email'] ) && ( $_POST['iXML_passwd'] ) ) {
			?>
				<script type = "text/javascript"> alert( "<?php _e( "Login and password don\'t match, try again, please", 'sitemap' );	?>" ) </script>
			<?php
			}
			else {
				if( $_POST['iXML_menu'] == "inf" ) {
					iXML_info_site( $au );//getting info about the site in google webmaster tools account
				}
				else if( $_POST['iXML_menu'] == "ad" ) {
					iXML_add_site( $au ); //adding site and verifying its ownership
					iXML_add_sitemap( $au );//adding sitemap file to the google webmaster tools account
				}
				else if( $_POST['iXML_menu'] == "del" ) {
					iXML_del_site( $au );//deleting site from google webmaster tools
				}
			}	
		}
	}
}

//============================================ Curl function ====================
if( ! function_exists( 'iXML_curl_funct' ) ) {
	function iXML_curl_funct( $au, $url_send, $type_request, $content ) {
		$headers  =  array ( "Content-type: application/atom+xml; charset=\"utf-8\"",
			"Authorization: GoogleLogin auth=" . $au
		);
		$chx = curl_init(); 
		curl_setopt( $chx, CURLOPT_URL, $url_send );
		curl_setopt( $chx, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $chx, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $chx, CURLOPT_RETURNTRANSFER, true );
		if ( $type_request == "GET" ) {
			curl_setopt( $chx, CURLOPT_HTTPGET, true );
		}
		if ( $type_request == "POST" ) {
			curl_setopt( $chx, CURLOPT_POST, true );
			curl_setopt( $chx, CURLOPT_POSTFIELDS, $content );
		}
		if ( $type_request == "DELETE" ) {
			curl_setopt( $chx, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		}
		if ( $type_request == "PUT" ) {
			curl_setopt( $chx, CURLOPT_CUSTOMREQUEST, 'PUT' );
			curl_setopt( $chx, CURLOPT_POSTFIELDS, $content );
		}
		$hasilx = curl_exec( $chx );
		curl_close( $chx );
		return $hasilx;
	}
}

//============================================ Function to get info about site ====================
if( ! function_exists( 'iXML_info_site' ) ) {	
	function iXML_info_site( $au ) {
		global $url_home;
		global $url;
		global $url_send;
		global $url_send_sitemap;
		$hasilx = iXML_curl_funct( $au, $url_send . $url, "GET", false );
		//========================= Getting info about site in google webmaster tools ====================
		echo "<h2><br />". __( "Info about this site in google webmaster tools", 'sitemap') ."</h2><br />";
		if ( $hasilx == "Site not found" ) {
			echo __( "This site is not added to the google webmaster tools account", 'sitemap');
		}
		else {
			$hasils = iXML_curl_funct( $au, $url_send . $url, "GET", false );
			echo "<pre>";
			$p = xml_parser_create();
			xml_parse_into_struct( $p, $hasils, $vals, $index );
			xml_parser_free( $p );  
			  foreach ( $vals as $val ) {
			  if( $val["tag"] == "WT:VERIFIED" )
					$ver = $val["value"];
				}
			$hasils = iXML_curl_funct( $au, $url_send_sitemap . $url . "/sitemaps/", "GET", false );
			echo "<pre>";
			$p = xml_parser_create();
			xml_parse_into_struct( $p, $hasils, $vals, $index );
			xml_parser_free( $p );  
			foreach ( $vals as $val ) {
			if( "WT:SITEMAP-STATUS" == $val["tag"] )
				$sit = $val["value"];
			}
			echo __( "Site url: ", 'sitemap') . $url_home . "<br />";
			echo __( "Site verification: ", 'sitemap'); 
			if( "true" == $ver ) 
				echo __( "verificated", 'sitemap') . "<br />"; 
			else 
				echo __( "non verificated", 'sitemap') . "<br />";
			echo __( "Sitemap file: ", 'sitemap');
			if( $sit ) 
				echo __( "added", 'sitemap') . "<br />"; 
			else 
				echo __( "not added", 'sitemap') . "<br />";
		}
	}
}

//============================================ Deleting site from google webmaster tools ====================
if( ! function_exists( 'iXML_del_site' ) ) {
	function iXML_del_site( $au ) {
		global $url, $url_send;
		$hasil3 = iXML_curl_funct( $au, $url_send. $url, "DELETE", false );
	}
}

//============================================ Adding site to the google webmaster tools ====================
if( ! function_exists( 'iXML_add_site' ) ) {
	function iXML_add_site( $au ) {
		global $url_home, $url, $url_send;
		$content = "<atom:entry xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:wt=\"http://schemas.google.com/webmasters/tools/2007\">"
		 ."<atom:content src=\"" . $url_home . "\" />"
		 ."</atom:entry>\n";
		$hasil1 = iXML_curl_funct( $au, $url_send, "POST", $content );
		preg_match( '/(google)[a-z0-9]*\.html/', $hasil1, $matches );
		//===================== Creating html file for verifying site ownership ====================
		$m1="../" . $matches[0];
		if( ! ( file_exists ( $m1 ) ) ) {
		$fp = fopen ("../" . $matches[0], "w+" );
		fwrite( $fp, "google-site-verification: " . $matches[0] );
		fclose ( $fp );
		}
		//============================= Verifying site ownership ====================
		$content  = "<atom:entry xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:wt=\"http://schemas.google.com/webmasters/tools/2007\">"
		."<atom:category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/webmasters/tools/2007#site-info'/>"
		."<wt:verification-method type=\"htmlpage\" in-use=\"true\"/>"
		."</atom:entry>";
		$hasil2 = iXML_curl_funct( $au, $url_send. $url, "PUT", $content );
	}
}

//============================================ Adding sitemap file ====================
if( ! function_exists( 'iXML_add_sitemap' ) ) {
	function iXML_add_sitemap( $au ) {
		global $url_home;
		global $url;
		global $url_send_sitemap;
		$content  = "<atom:entry xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:wt=\"http://schemas.google.com/webmasters/tools/2007\">"
		."<atom:id>" . $url_home . "/sitemap.xml</atom:id>"
		."<atom:category scheme=\"http://schemas.google.com/g/2005#kind\" term=\"http://schemas.google.com/webmasters/tools/2007#sitemap-regular\"/>"
		."<wt:sitemap-type>WEB</wt:sitemap-type>"
		."</atom:entry>";
		$hasil1 = iXML_curl_funct( $au, $url_send_sitemap . $url . "/sitemaps/", "POST", $content );
	}
}

//============================================ Adding setting link in activate plugin page ====================
if( ! function_exists( 'iXML_action_links' ) ) {
	function iXML_action_links( $links, $file ) {
		//Static so we don't call plugin_basename on every plugin row.
		static $this_plugin;
		if ( ! $this_plugin ) 
			$this_plugin = plugin_basename( __FILE__ );
		if ( $file == $this_plugin ) {
			 $settings_link = '<a href="/wp-admin/options-general.php?page=ixml/iXML.php">' . __( 'Settings', 'sitemap' ) . '</a>';
			 array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

if ( ! function_exists ( 'iXML_plugin_init' ) ) {
	function iXML_plugin_init() {
		load_plugin_textdomain( 'sitemap', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
	}
}













if ( !class_exists ('wp_iXML')) {
	class wp_iXML {

	function iXML_setup() {
		global $wpdb;
		$wpdb->query("ALTER TABLE $wpdb->posts ADD COLUMN iXML_exclude tinyint(1) unsigned default '0'");
	}
			
	function wp_iXML_exclude($pID) {
		global $wpdb;
		$iXML_exclude_me = $_REQUEST['iXML_exclude_me'];
		$wpdb->query("UPDATE $wpdb->posts SET iXML_exclude = ".mysql_escape_string($iXML_exclude_me)." WHERE ID = $pID");
	}
	
	function iXML_options_box() {
		add_meta_box('iXML', 'iXML', array('wp_iXML','iXML_box'), 'page', 'side', 'low');
		add_meta_box('iXML', 'iXML', array('wp_iXML','iXML_box'), 'post', 'side', 'low');
	}
	
	function iXML_box() {
		global $post;
		$iXML_exclude = $post->iXML_exclude;
	?>
		<fieldset id="mycustom-div">
		<div>
		<p>
		
			<input type="checkbox" name="iXML_exclude_me" value="1" id="iXML_exclude_me" <?php if ($iXML_exclude == "1") echo 'checked'; ?>> <label for="iXML_exclude_me" >Exclude from sitemap</label>
		</p>
		</div>
		</fieldset>
	<?php
	}

	/*function add_iXML_exclude_tag() {
		global $post;
		if ( is_home() || is_single() || is_page() ) {
		$iXML_exclude = (empty($post->iXML_exclude)) ? 'index, follow' : $post->iXML_exclude;
		echo '<meta name="robots" content="'.$iXML_exclude.'" />'."\n";
		} elseif ( is_category() || is_tag() || is_archive() ) {
		echo '<meta name="robots" content="noindex, follow" />'."\n";
		} else {
		echo '<meta name="robots" content="noindex, nofollow" />'."\n";
		}
	}*/
	
	} // class iXML_exclude_plugin
}

add_action('init', array('wp_iXML','iXML_setup'));
add_action('admin_menu', array('wp_iXML','iXML_options_box'));
add_action('wp_insert_post', array('wp_iXML','wp_iXML_exclude'));
register_activation_hook( __FILE__, 'register_iXML_settings'); // activate plugin
register_uninstall_hook( __FILE__, 'delete_iXML_settings'); // uninstall plugin

add_action( 'init', 'iXML_settings_global' );

add_action( 'admin_enqueue_scripts', 'iXML_add_my_stylesheet' );
add_action( 'wp_enqueue_scripts', 'iXML_add_my_stylesheet' );
//add_action( 'admin_init', 'iXML_plugin_init' );
add_action( 'admin_menu', 'iXML_plugin_init' );
add_action( 'admin_menu', 'iXML_add_pages' );
add_filter( 'plugin_action_links', 'iXML_action_links', 10, 2 );

?>
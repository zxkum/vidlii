<?php
    namespace Vidlii\Vidlii;

    class Engine {
        protected $DB, $_USER, $_PAGE, $_GUMP, $_THEMES;
        
        function __construct() {
            // CONSTANTS
            define("ROOT_FOLDER", $_SERVER["DOCUMENT_ROOT"]);

            define("UPLOAD_LIMIT", 1024 * 1024 * 1024 * 2.01);
            define("ALLOWED_FORMATS", ["FLV", "MP4", "WMV", "AVI", "MOV", "M4V", "MPG", "MPEG", "WEBM", "MOV", "MKV", "3GP"]);

            define("ADMIN_PASSWORD", "poops");

            define("DB_HOST", "localhost");
            define("DB_DATABASE", "vidlii");
            define("DB_USER", "root");
            define("DB_PASSWORD", "");
            define("DB_CHARSET", "latin1");

            define("CSS_FILE", "/css/m.css?8");
            define("PROFILE_CSS_FILE", "/css/profile.css?5");
            define("COSMIC_CSS_FILE", "/css/cosmicpanda.css?5");
            define("PROFILE_JS_FILE", "/js/profile.js?9");
            define("COSMIC_JS_FILE", "/js/cosmicpanda.js?3");
            define("MAIN_JS_FILE", "/js/main3.js?22");

            ini_set('session.cookie_httponly', 1); // ?
            session_start(["cookie_lifetime" => 0, "gc_maxlifetime" => 455800]);

            if(!isset($_SESSION["sec_actions"])) $_SESSION["sec_actions"] = 0;
            if(isset($_COOKIE["css"]) && $_COOKIE["css"] == "deleted") setcookie("css", null, -1);
            
            // SETUP CLASSES
            $this->DB = new Database(false);
            $this->_USER = new User(NULL, $this->DB, true);
            $this->_PAGE = new Page();
            $this->_GUMP = new \GUMP();
            $this->_THEMES = new Themes($this->DB, $this->_USER);
        }

        public function user_ip() {
            return getenv('HTTP_CLIENT_IP') ? : 
                    getenv('HTTP_X_FORWARDED_FOR') ? : 
                    getenv('HTTP_X_FORWARDED') ? : 
                    getenv('HTTP_FORWARDED_FOR') ? : 
                    getenv('HTTP_FORWARDED') ? : 
                    getenv('REMOTE_ADDR');
        }
        
        public function in_array_r($needle, $haystack, $strict = false) {
            foreach($haystack as $item) {
                if(($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
                    return true;
                }
            }
            return false;
        }
        
        public function showBBcodes($text) {
            $find = array(
                '~\[b\](.*?)\[/b\]~s',
                '~\[i\](.*?)\[/i\]~s',
                '~\[u\](.*?)\[/u\]~s'
            );
            $replace = array(
                '<b>$1</b>',
                '<i>$1</i>',
                '<t style="text-decoration:underline;">$1</t>'
            );
            return preg_replace($find,$replace,$text);
        }
        
        public function hashtag_search($text) {
            return preg_replace('/(?<!\S)#([0-9a-zA-Z]+)/', '<a href="/results?q=$1">#$1</a>', $text);
        }
        
        public function process_clickable_length($str) {
            $ex = array_reverse(explode(":", $str[1]));
            $sec = 0;
            for($i = 0; $i < count($ex); $i++) {
                $sec += (int)$ex[$i] * pow(60, $i);
            }
            return "<a href=\"#t=$sec\" onclick=\"$(window).trigger('hashchange')\">$str[1]</a>";
        }
        
        public function mention($text) {
            $text = preg_replace_callback('/\b((\d+:){1,2}+\d+)\b/', 'process_clickable_length', $text);
            return preg_replace('/(?<!\S)@([0-9a-zA-Z]+)/', '<a href="/user/$1">@$1</a>', $text);
        }
        
        public function notification($Message,$Redirect,$Color = "red") {
            $_SESSION["notification"] = $Message;
            $_SESSION["n_color"] = $Color;
            if($Redirect != false) redirect($Redirect);
        }
        
        public function clean($string) {
           $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
           $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
           return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
        }
        
        public function isTorRequest() {
            if(isset($_SERVER["HTTP_CF_IPCOUNTRY"]) && $_SERVER["HTTP_CF_IPCOUNTRY"] == "T1") {
                return true;
            }
            return false;
        }
        
        public function DoLinks($text) {
            return preg_replace('!(((f|ht)tp(s) ? : //)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1">$1</a>', $text);
        }
        
        public function sql_IN_fix($Array,$key = NULL) {
            $New_Array = "";
            if(!isset($key)) {
                foreach($Array as $Value) {
                    $New_Array .= "'" . $Value . "'" . ',';
                }
            } else {
                foreach($Array as $Key => $Value) {
                    $New_Array .= "'" . $Key . "'" . ',';
                }
            }
            return substr($New_Array,0,strlen($New_Array) - 1);
        }
        
        public function url_parameter($URL,$Parameter) {
            parse_str(parse_url($URL)['query'], $Query);
            return $Query[$Parameter];
        }
        
        public function get_time_ago($time) {
            $time = time() - strtotime($time);
            $time = ($time < 1)? 1 : $time;
            $tokens = array (
                31536000 => 'year',
                2592000 => 'month',
                604800 => 'week',
                86400 => 'day',
                3600 => 'hour',
                60 => 'minute',
                1 => 'second'
            );
        
            foreach ($tokens as $unit => $text) {
                if($time < $unit) continue;
                $numberOfUnits = floor($time / $unit);
                return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s ago':' ago');
            }
            foreach ($tokens as $unit => $text) {
                if($time < $unit) continue;
                $numberOfUnits = floor($time / $unit);
                return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s ago':' ago');
            }
            foreach ($tokens as $unit => $text) {
                if($time < $unit) continue;
                $numberOfUnits = floor($time / $unit);
                return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s ago':' ago');
            }
        }
        
        public function subscribe_button($User, $Is_Subscribed = false, $Blocked = false) {
            if($this->_USER->logged_in) {
                if($this->_USER->username !== $User && !$Blocked) {
                    if($Is_Subscribed) {
                        return '<a href="/ajax/df/subscribe?u='.$User.'" class="sub is"><img src="/img/sub_check.png">Subscribed</a>';
                    } else {
                        return '<a href="/ajax/df/subscribe?u='.$User.'" class="sub"><img src="/img/sub_check.png">Subscribe</a>';
                    }
                } else {
                    return '<a href="javascript:void(0)" onclick="alert(\'You cannot subscribe to yourself!\')" class="sub"><img src="/img/sub_check.png">Subscribe</a>';
                }
            } elseif(!$Blocked) {
                return '<a href="javascript:void(0)" onclick="alert(\'You must be logged in to subscribe!\')" class="sub"><img src="/img/sub_check.png">Subscribe</a>';
            } else {
                return '<a href="javascript:void(0)" onclick="alert(\'You cannot interact with this user!\')" class="sub"><img src="/img/sub_check.png">Subscribe</a>';
            }
        }
        
        public function convert_filesize($Bytes, $Format) {
            switch(mb_strtolower($Format)) {
                case "kb": return $Bytes / 1024; break;
                case "mb": return $Bytes / 1048576; break;
                case "gb": return $Bytes / 1073741824; break;
            }
        }
        
        public function browser_name() {
            $AGENT = $_SERVER['HTTP_USER_AGENT'];
            if(mb_strpos($AGENT,"Chrome") !== false && mb_strpos($AGENT,"OPR") === false) {
                return "Chrome";
            } elseif(mb_strpos($AGENT,"Chrome") !== false && mb_strpos($AGENT, "OPR") !== false) {
                return "Opera";
            } elseif(mb_strpos($AGENT,"Firefox") !== false) {
                return "Firefox";
            } elseif(mb_strpos($AGENT,"Trident") !== false) {
                return "IE";
            } elseif(mb_strpos($AGENT,"Edge") !== false) {
                return "Edge";
            } elseif(mb_strpos($AGENT,"Safari") !== false) {
                return "Safari";
            }
            return "Unknown";
        }
        
        public function old_show_ratings($Ratings,$width,$height) {
            if(is_array($Ratings)) {
                $Star_1 = $Ratings["1_star"];
                $Star_2 = $Ratings["2_star"];
                $Star_3 = $Ratings["3_star"];
                $Star_4 = $Ratings["4_star"];
                $Star_5 = $Ratings["5_star"];
        
                $Rating_Num = $Star_1 + $Star_2 + $Star_3 + $Star_4 + $Star_5;
        
                if($Rating_Num > 0) {
                    $Rating = ($Star_1 + $Star_2 * 2 + $Star_3 * 3 + $Star_4 * 4 + $Star_5 * 5) / $Rating_Num;
                } else {
                    $Rating = 0;
                }
            } else {
                $Rating = $Ratings;
            }
        
            $Full_Stars = substr($Rating, 0, 1);
            $Half_Stars = substr($Rating, 2, 1);
        
            $StarNum    = 0;
            for($x = 0;$x < $Full_Stars;$x++) {
                $StarNum++;
                echo "<img src='/img/full_star.png' width='$width' height='$height'> ";
            }
            if($Half_Stars !== false) {
                $StarNum++;
                if($Full_Stars !== "4") {
                    echo "<img src='/img/half_star.png' width='$width' height='$height'> ";
                } else {
                    if($Half_Stars == "8" or $Half_Stars == "9") {
                        echo "<img src='/img/full_star.png' width='$width' height='$height'> ";
                    } else {
                        echo "<img src='/img/full_star.png' width='$width' height='$height'> ";
                    }
                }
            }
            while($StarNum !== 5) {
                $StarNum++;
                echo "<img src='/img/no_star.png' width='$width' height='$height'> ";
            }
        }
        
        public function show_ratings($Ratings,$width,$height) {
            if(is_array($Ratings)) {
                $Star_1 = $Ratings["1_star"] / 5;
                $Star_2 = $Ratings["2_star"] / 4;
                $Star_3 = $Ratings["3_star"] / 3;
                $Star_4 = $Ratings["4_star"] / 2;
                $Star_5 = $Ratings["5_star"] / 1;
            
                $Rating_Num = $Star_1 + $Star_2 + $Star_3 + $Star_4 + $Star_5;
        
                if($Rating_Num > 0) {
                    $Rating = ($Star_1 + $Star_2 * 2 + $Star_3 * 3 + $Star_4 * 4 + $Star_5 * 5) / $Rating_Num;
                } else {
                    $Rating = 0;
                }
            } else {
                $Rating = $Ratings;
            }
        
            $Full_Stars = substr($Rating, 0, 1);
            $Half_Stars = substr($Rating, 2, 1);
        
            $StarNum    = 0;
            for($x = 0; $x < $Full_Stars;$x++) {
                $StarNum++;
                echo "<img src='/img/full_star.png' width='$width' height='$height'>";
            }
            if($Half_Stars !== false) {
                $StarNum++;
                if($Full_Stars !== "4") {
                    echo "<img src='/img/half_star.png' width='$width' height='$height'>";
                } else {
                    if($Half_Stars == "8" or $Half_Stars == "9") {
                        echo "<img src='/img/full_star.png' width='$width' height='$height'>";
                    } else {
                        echo "<img src='/img/full_star.png' width='$width' height='$height'>";
                    }
                }
            }
            while($StarNum !== 5) {
                $StarNum++;
                echo "<img src='/img/no_star.png' width='$width' height='$height'>";
            }
        }
        
        public function return_ratings($Ratings,$width,$height) {
            $Return = "";
            if(is_array($Ratings)) {
                $Star_1 = $Ratings["1_star"];
                $Star_2 = $Ratings["2_star"];
                $Star_3 = $Ratings["3_star"];
                $Star_4 = $Ratings["4_star"];
                $Star_5 = $Ratings["5_star"];
        
                $Rating_Num = $Star_1 + $Star_2 + $Star_3 + $Star_4 + $Star_5;
        
                if($Rating_Num > 0) {
                    $Rating = ($Star_1 + $Star_2 * 2 + $Star_3 * 3 + $Star_4 * 4 + $Star_5 * 5) / $Rating_Num;
                } else {
                    $Rating = 0;
                }
            } else {
                $Rating = $Ratings;
            }
        
            $Full_Stars = substr($Rating, 0, 1);
            $Half_Stars = substr($Rating, 2, 1);
        
            $StarNum    = 0;
            for($x = 0;$x < $Full_Stars;$x++) {
                $StarNum++;
                $Return .= "<img src='/img/full_star.png' width='$width' height='$height'>";
            }
            if($Half_Stars !== false) {
                $StarNum++;
                if($Full_Stars !== "4") {
                    $Return .= "<img src='/img/half_star.png' width='$width' height='$height'>";
                } else {
                    if($Half_Stars == "8" or $Half_Stars == "9") {
                        $Return .= "<img src='/img/full_star.png' width='$width' height='$height'>";
                    } else {
                        $Return .= "<img src='/img/full_star.png' width='$width' height='$height'>";
                    }
                }
            }
            while($StarNum !== 5) {
                $StarNum++;
                $Return .=  "<img src='/img/no_star.png' width='$width' height='$height'>";
            }
            return $Return;
        }
        
        public function get_time($time) {
            if(!is_numeric($time)) {
                return date("h:i:s A", strtotime($time));
            } else {
                return date("h:i:s A", $time);
            }
        }
        
        public function random_string($Characters, $Length) {
            if(empty($Characters) || $Characters == "") $Characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $charactersLength = mb_strlen($Characters);
            $randomString = '';
            for ($i = 0; $i < $Length; $i++) {
                $randomString .= $Characters[mt_rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }
        
        public function redirect($url = "") {
            if($url == "") return;
            
            if(!headers_sent()) {
                header('Location: '.$url);
                exit;
            } else {
                echo "<script>window.location.href='$url'</script>";
                exit;
            }
        }
        
        public function get_date($date) {
            if(!is_numeric($date)) {
                return date("M d, Y", strtotime($date));
            } else {
                return date("M d, Y", $date);
            }
        }
        
        public function email_domain($Email) {
            return substr(strrchr($Email, "@"), 1);
        }
        
        public function video_thumbnail($URL,$LENGTH,$Width,$Height,$Title = NULL) {
            if(!empty($LENGTH) || $LENGTH == "0") { $Length = seconds_to_time((int)$LENGTH); } else { $Length = $LENGTH; }
            if(file_exists("usfi/thmp/$URL.jpg")) { $Thumbnail = "/usfi/thmp/$URL.jpg"; } else { $Thumbnail = "/img/no_th.jpg"; }
            return '<div class="th"><div class="th_t">'.$Length.'</div><a href="/watch?v='.$URL.'"><img class="vid_th" loading="lazy" src="'.$Thumbnail.'" width="'.$Width.'" height="'.$Height.'"></a></div>';
        }
        
        public function user_avatar($User,$Width,$Height,$Avatar,$Border = "") {
            if(strpos($Avatar,"u=") !== false) { $Avatar = str_replace("u=","",$Avatar); $Folder = "avt"; } else { $Upload = false; $Folder = "thmp"; }
            if(empty($Avatar) or !file_exists("usfi/$Folder/$Avatar.jpg")) {
                $Avatar = "/img/no_avatar.png";
            } else {
                if($Folder == "avt") {
                    $Avatar = "/usfi/avt/$Avatar.jpg";
                } else {
                    $Avatar = "/usfi/thmp/$Avatar.jpg";
                }
            }
            return '<a href="/user/'.$User.'"><img src="'.$Avatar.'" width="'.$Width.'" loading="lazy" height="'.$Height.'" class="avt '.$Border.'" alt="'.$User.'"></a>';
        }
        
        public function user_avatar2($User,$Width,$Height,$Avatar,$Extra_Class = "") {
            if(strpos($Avatar,"u=") !== false) { $Avatar = str_replace("u=","",$Avatar); $Folder = "avt"; } else { $Upload = false; $Folder = "thmp"; }
        
            if(empty($Avatar) or !file_exists("usfi/$Folder/$Avatar.jpg")) {
                $Avatar = "/img/no.png";
            } else {
                if($Folder == "avt") {
                    $Avatar = "/usfi/avt/$Avatar.jpg";
                } else {
                    $Avatar = "/usfi/thmp/$Avatar.jpg";
                }
            }
            return '<a href="/user/'.$User.'"><img src="'.$Avatar.'" width="'.$Width.'" height="'.$Height.'" class="avt2 '.$Extra_Class.'" alt="'.$User.'"></a>';
        }
        
        public function get_age($Date) {
                return date_diff(date_create($Date), date_create('today'))->y;
            }
        
            public function time_ago($time) {
                $time = time() - strtotime($time);
                $time = ($time < 1)? 1 : $time;
                $tokens = array (
                    31536000 => 'year',
                    2592000 => 'month',
                    604800 => 'week',
                    86400 => 'day',
                    3600 => 'hour',
                    60 => 'minute',
                    1 => 'second'
                );
        
                foreach ($tokens as $unit => $text) {
                    if($time < $unit) continue;
                    $numberOfUnits = floor($time / $unit);
                    return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s ago':' ago');
                }
            }
        
        public function colourBrightness($hex, $percent) {
            // Work out if hash given
            $hash = '';
            if(stristr($hex,'#')) {
                $hex = str_replace('#','',$hex);
                $hash = '#';
            }
            /// HEX TO RGB
            $rgb = array(hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)));
            //// CALCULATE
            for ($i=0; $i<3; $i++) {
                // See if brighter or darker
                if($percent > 0) {
                    // Lighter
                    $rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1-$percent));
                } else {
                    // Darker
                    $positivePercent = $percent - ($percent*2);
                    $rgb[$i] = round($rgb[$i] * $positivePercent) + round(0 * (1-$positivePercent));
                }
                // In case rounding up causes us to go to 256
                if($rgb[$i] > 255) {
                    $rgb[$i] = 255;
                }
            }
            //// RBG to Hex
            $hex = '';
            for($i=0; $i < 3; $i++) {
                // Convert the decimal digit to hex
                $hexDigit = dechex($rgb[$i]);
                // Add a leading zero if necessary
                if(strlen($hexDigit) == 1) {
                    $hexDigit = "0" . $hexDigit;
                }
                // Append to the hex string
                $hex .= $hexDigit;
            }
            return $hash.$hex;
        }
        
        
        public function cut_string($text,$length) {
                if(strlen($text) > $length) {
                    return substr($text,0,$length)."...";
                } else {
                    return $text;
                }
            }
        
        public function subscribe_button2($For, $Blocked = false) {
            if($this->_USER->logged_in && $Blocked == false) {
                if($this->_USER->Is_Activated) {
                    if($this->_USER->username !== $For) {
                        if($this->_USER->is_subscribed_to($For)) {
                            return '<a href="javascript:void(0)" class="yel_btn sub_button" user="'.$For.'">Unsubscribe</a>';
                        } else {
                            return '<a href="javascript:void(0)" class="yel_btn sub_button" user="'.$For.'">Subscribe</a>';
                        }
                    } else {
                        return '<a href="javascript:void(0)" class="yel_btn" onclick="alert('."'No need to subscribe to yourself!'".')">Subscribe</a>';
                    }
                } else {
                    return '<a href="javascript:void(0)" class="yel_btn" onclick="alert('."'Please click the activation link we sent via email to subscribe!'".')">Subscribe</a>';
                }
            } elseif(!$this->_USER->logged_in) {
                return '<a href="javascript:void(0)" class="yel_btn" onclick="alert('."'You must be logged in to subscribe!'".')">Subscribe</a>';
            } else {
                return '<a href="javascript:void(0)" class="yel_btn" onclick="alert('."'You cannot interact with this user!'".')">Subscribe</a>';
            }
        }
        
        public function limit_text($text, $length) {
            $length = abs((int)$length);
            if(mb_strlen($text) > $length) {
                $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1', $text);
            }
            return($text);
        }
        
        
        public function check_captcha() {
        //	if(isset($_POST["g-recaptcha-response"]) and $_POST["g-recaptcha-response"]) {
        //		return true;
        //	} else {
        //		return false;
        //	}
            if(isset($_POST["_KAPTCHA"])) return kaptcha_validate($_POST["_KAPTCHA_KEY"]);
            return false;
        }
        
        public function previous_page() {
            if(!empty($_SESSION["previous_page"])) {
                return $_SESSION["previous_page"];
            } else {
                return "/";
            }
        }
        
        public function seconds_to_time($Seconds) {
            $min = intval($Seconds / 60);
            return $min . ':' . str_pad(($Seconds % 60), 2, '0', STR_PAD_LEFT);
        }
        
        public function return_category($Number) {
            $Categories = array(1 => "Film & Animation", 2 => "Autos & Vehicles", 3 => "Music", 4 => "Pets & Animals", 5 => "Sports", 6 => "Travel & Events", 7 => "Gaming", 8 => "People & Blogs", 9 => "Comedy", 10 => "Entertainment", 11 => "News & Politics", 12 => "Howto & Style", 13 => "Education", 14 => "Science & Technology", 15 => "Nonprofits & Activism");
            return $Categories[$Number];
        }
        
        public function return_categories() {
            return array(1 => "Film & Animation", 2 => "Autos & Vehicles", 3 => "Music", 4 => "Pets & Animals", 5 => "Sports", 6 => "Travel & Events", 7 => "Gaming", 8 => "People & Blogs", 9 => "Comedy", 10 => "Entertainment", 11 => "News & Politics", 12 => "Howto & Style", 13 => "Education", 14 => "Science & Technology", 15 => "Nonprofits & Activism");
        }
        
        public function hexToRgb($hex, $alpha = false) {
            $hex      = str_replace('#', '', $hex);
            $length   = strlen($hex);
            $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
            $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
            $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
            if( $alpha ) {
                $rgb['a'] = $alpha;
            }
            return "rgba(".$rgb["r"].",".$rgb["g"].",".$rgb["b"].",".$rgb["a"].")";
        }

        // advanced functions such as blocked IP checks, auto links and etc

        public function auto_link_callback($matches){
            return (strtolower($matches[3]) == "</a>") ? $matches[0] : preg_replace('/([a-zA-Z]+)(:\/\/[\w\+\$\;\?\.\{\}%,!#~*\/:@&=_-]+)/u', '<a href="$1$2" target="_blank" rel="nofollow noreferrer">$1$2</a>', $matches[0]);
        }
        
        public function auto_link($proto){
            $proto = preg_replace('|<br\s*/?>|',"\n",$proto);
            $proto = preg_replace_callback('/(>|^)([^<]+?)(<.*?>|$)/m','auto_link_callback',$proto);
            return str_replace("\n",'<br />',$proto);
        }
        
        public function baniphostdnsblcheck($IP, $HOST, &$baninfo){
            $BANPATTERN = array(); // IP/Hostname
            $DNSBLservers = array(0, 'sbl-xbl.spamhaus.org', 'list.dsbl.org', 'bl.blbl.org', 'bl.spamcop.net'); 
            $DNSBLWHlist = array(); // DNSBL whitelist
        
            // IP/Hostname Check
            $HOST = strtolower($HOST);
            $checkTwice = ($IP != $HOST);
            $IsBanned = false;
            foreach($BANPATTERN as $pattern){
                $slash = substr_count($pattern, '/');
                if($slash==2){ // RegExp
                    $pattern .= 'i';
                }elseif($slash==1){ // CIDR Notation
                    if(match_cidr($IP, $pattern)){ $IsBanned = true; break; }
                    continue;
                }elseif(strpos($pattern, '*')!==false || strpos($pattern, '?')!==false){ // Wildcard
                    $pattern = '/^'.str_replace(array('.', '*', '?'), array('\.', '.*', '.?'), $pattern).'$/i';
                }else{ // Full-text
                    if($IP==$pattern || ($checkTwice && $HOST==strtolower($pattern))){ $IsBanned = true; break; }
                    continue;
                }
                if(preg_match($pattern, $HOST) || ($checkTwice && preg_match($pattern, $IP))){ $IsBanned = true; break; }
            }
            if($IsBanned){ $baninfo = 'Listed in IP/Hostname Blacklist'; return true; }
        
            if(!$DNSBLservers[0]) return false; // Skip check
            if(array_search($IP, $DNSBLWHlist)!==false) return false;
            $rev = implode('.', array_reverse(explode('.', $IP)));
            $lastPoint = count($DNSBLservers) - 1; if($DNSBLservers[0] < $lastPoint) $lastPoint = $DNSBLservers[0];
            $isListed = false;
            for($i = 1; $i <= $lastPoint; $i++){
                $query = $rev.'.'.$DNSBLservers[$i].'.'; // FQDN
                $result = gethostbyname($query);
                if($result && ($result != $query)){ $isListed = $DNSBLservers[$i]; break; }
            }
            if($isListed){ $baninfo = "Listed in DNSBL($isListed) Blacklist"; return true; }
            return false;
        }

        public function match_cidr($addr, $cidr) {
            list($ip, $mask) = explode('/', $cidr);
            return (ip2long($addr) >> (32 - $mask) == ip2long($ip.str_repeat('.0', 3 - substr_count($ip, '.'))) >> (32 - $mask));
        }
        
        public function getremoteaddr_cloudflare() {
            $addr = $_SERVER['REMOTE_ADDR'];
            $cloudflare_v4 = array('199.27.128.0/21', '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/12');
            $cloudflare_v6 = array('2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32');
        
            if(filter_var($addr, FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) { //v4 address
                foreach ($cloudflare_v4 as &$cidr) {
                    if(match_cidr($addr, $cidr)) {
                        return $_SERVER['HTTP_CF_CONNECTING_IP'];
                    }
                }
            } else { // v6 address
                foreach ($cloudflare_v6 as &$cidr) {
                    if(match_cidrv6($addr, $cidr)) {
                        return $_SERVER['HTTP_CF_CONNECTING_IP'];
                    }
                }
            }
            return '';
        }
        
        public function getremoteaddr_openshift() {
            if (isset($_ENV['OPENSHIFT_REPO_DIR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            return '';
        }
        
        public function getremoteaddr_proxy() {
            global $PROXYHEADERlist;
        
            if (!defined('TRUST_HTTP_X_FORWARDED_FOR') || !TRUST_HTTP_X_FORWARDED_FOR) {
                return '';
            }
            $ip='';
            $proxy = $PROXYHEADERlist;
        
            foreach ($proxy as $key) {
                if (array_key_exists($key, $_SERVER)) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        $ip = trim($ip);
                        if (filter_var($ip, FILTER_VALIDATE_IP,FILTER_FLAG_IPV4 |FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !==false) {
                            return $ip;
                        }
                    }
                }
            }
        
            return '';
        }
        
        public function getremoteaddr() {
            static $ip_cache;
            if ($ip_cache) return $ip_cache;
        
            $ipCloudFlare = getremoteaddr_cloudflare();
            if (!empty($ipCloudFlare)) {
                return $ip_cache = $ipCloudFlare;
            }
        
            $ipOpenShift = getremoteaddr_openshift();
            if (!empty($ipOpenShift)) {
                return $ip_cache = $ipOpenShift;
            }
        
            $ipProxy = getremoteaddr_proxy();
            if (!empty($ipProxy)) {
                return $ip_cache = $ipProxy;
            }
        
            return $ip_cache = $_SERVER['REMOTE_ADDR'];
        }
        
        public function anti_sakura($str) {
            return preg_match('/[\x{E000}-\x{F848}]/u', $str);
        }
        
        /* Error */
        public function error($title='ERROR', $title2='Something went wrong', $description='') {
            ?><!DOCTYPE html>
        <html>
            <head>
                <meta http-equiv="content-type" content="text/html; charset=utf-8" />
                <title><?=$title?></title>
                <meta name="robots" content="nofollow,noarchive" />
                <!-- META -->
                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
                <!-- EVIL CACHE -->
                <meta http-equiv="cache-control" content="no-cache" />
                <meta http-equiv="expires" content="0" />
                <meta http-equiv="pragma" content="no-cache" />
                <!-- STYLE -->
                <link type="text/css" rel="stylesheet" href="common.css" media="all" />
                <style>
        body {
            background-image: url("bloodybrickwall.png");
            color: #000;
            font-size: 90%;
        }
        
        .doc {
            background-color: #211A;
            color: #EEE;
            padding: 0.5em 0.2em;
        }
        
        a { color: #77F; }
        a:hover { color: #F77; }
                </style>
            </head>
            <body dir="ltr" bgcolor="#F00" text="#000">
                <div id="upper" align="RIGHT">
                    [<a href="javascript:void(0);" onclick="history.back();">Return</a>]
                </div>
                <h1><?=$title2?></h1>
                <?=$description?"<p class=\"doc\">$description</p>":'<!--NO DESCRIPTION-->'?>
            </body>
        </html><?php
        
            exit;
        }
        
        /* MySQLi functions */
        
        public function HTM_sqltable($result, $fieldtl=[], $input='') {
            mysqli_data_seek($result, 0);
            mysqli_field_seek($result, 0);
            $htm = '<table class="n_sql n_table" border="1" cellspacing="0"><thead><tr>';
            if ($input) {
                $htm.= '<th></th>';
            }
            while ($field=mysqli_fetch_field($result)) {
                $htm.= '<th class="n_col n_col_'.$field->name.'"><nobr>'.
                    ($fieldtl[$field->name]??('<small>'.ucfirst($field->name).'</small>')).'</nobr></th>';
            }
            $htm.= '</tr></thead><tbody>';
            while ($ass=mysqli_fetch_assoc($result)) {
                $htm.= '<tr>';
                if ($input) {
                    $htm.= '<td><input type="checkbox" name="'.$ass[$input].'" value="true" /></td>';
                }
                foreach ($ass as $key=>$val) {
                    $htm.= "<td class=\"n_col n_col_$key\">$val</td>";
                }
                $htm.= '</tr>';
            }
            $htm.= '</tbody></table>';
            return $htm;
        }
        
        /* HTML functions */
        public function HTM_redirect($to, $time=0) {
            if($to=='back') {
                $to = $_SERVER['HTTP_REFERER']??'';
            }
            $tojs = $to==($_SERVER['HTTP_REFERER']??'') ? 'history.go(-1);' : "location.href=\"$to\";";
            ?><!DOCTYPE html>
        <html>
            <head>
                <meta http-equiv="content-type" content="text/html; charset=utf-8" />
                <title>Redirecting...</title>
                <meta name="robots" content="nofollow,noarchive" />
                <!-- META -->
                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
                <!-- EVIL CACHE -->
                <meta http-equiv="cache-control" content="no-cache" />
                <meta http-equiv="expires" content="0" />
                <meta http-equiv="pragma" content="no-cache" />
                <!-- SCRIPT -->
                <meta http-equiv="refresh" content="<?=$time+1?>;URL=<?=$to?>" />
                <script>
                    setTimeout(function(){<?=$tojs?>}, <?=$time*1000?>);
                </script>
            </head>
            <body>
                Redirecting...
                <p>If your browser doesn't redirect for you, please click: <a href="<?=$to?>" onclick="event.preventDefault();<?=$tojs?>">Go</a></p>
            </body>
        </html>
        <?php
            exit;
        }
    }
?>
<?

include_once $gPathDefault . "gOutput.php";

class gDashboard
{
    private string $content = "";
    private array $rowSet = [];
    private bool $useMarkdown = true;

    public function __construct($json)
    {
        $jarr = cssDecode($json);
        if ($jarr['markdown'] == 'false') {
            $this->useMarkdown = false;
        }
    }


    public function resetSetRow(): void
    {
        $this->rowSet = [];
        for($i = 0; $i < 12; $i++) {
            $this->rowSet[$i] = 0;
        }
    }


    public function setColumnsWidth(...$set): void
    {
        $this->resetSetRow();
        $i = 0;
        foreach($set as $row) {
            $this->rowSet[$i] = $row;
            $i++;
        }
    }


    public function addRow(...$contents): void
    {
        $count = count($contents);
        $ttl = 0;
        for ($i = 0; $i < $count; $i++) {
            $ttl+=$this->rowSet[$i];
        }

        $i = 0;
        $this->content .= "<div class=\"row\">\n";
        foreach ($contents as $content) {
            $x  = round(round(12 * $this->rowSet[$i])/$ttl);
            $lg = $md = $sm = $x;
            if ($x == 3) {
                $sm = 6;
            }

            if ($x == 2) {
                $sm = 4;
            }

            $class = "col-lg-$lg col-md-$md col-sm-$sm col-xs-12";
            $this->content .= "<div class=\"$class\">";
            $this->content .= $content;
            $this->content .= "</div>\n";
            $i++;
        }

        $this->content .= "</div>\n";
    }


    public function format($json, string $content): string|array
    {
        global $gPathDefault;
        $jarr = cssDecode($json);
        $date = '';
        $html = "\n";
        $mai  = "<";
        $mab  = "</";
        $men  = ">";
        $end  = "";
        if ($this->useMarkdown) {
            $mai = $men = $mab = "";
            $end = "\n\n";
            $size['title'] = '## ';
            $size['subTitle'] = '#### ';
            $size['content']  = '';
            switch ($jarr['size']) {
                case 'small':
                    $size['title'] = '### ';
                    $size['subTitle'] = '##### ';
                    $size['content']  = '';
                    break;

                case 'tiny':
                    $size['title'] = '#### ';
                    $size['subTitle'] = '';
                    $size['content']  = '';
                    break;
            }

            if ($jarr['date']) {
                $date = substr(gDateTime($jarr['date']), 0, 14);
            }

            if ($jarr['tags']) {
                $tags = $jarr['tags'];
            }

            if ($jarr['title']) {
                if ($jarr['style']) {
                    $style = "{.text-" . $jarr['style'] . "}";
                }

                $html .= $size['title'] . " " . $jarr['title'] . " " . $style . $end;
            }

            if ($jarr['subTitle']) {
                $html .= $size['subTitle'] . " " . $jarr['subTitle'] . $end;
            }

            $add = [];
            if ($date !== '' && $date !== '0') {
                $add[] = $date;
            }

            if ($tags) {
                $add[] = $tags;
            }

            $html .= "<sup>" . implode(" ",$add) . $end . "</sup>";
            $html .= $size['content'] . $men . $content . $mab . $size['content'] . $men;
            $html = gMarkdown($html);
            // require_once $gPathDefault."lib/parsedown-master/Parsedown.php";
            // require_once $gPathDefault."lib/parsedown-master/ParsedownExtra.php";
            // $Parsedown = new ParsedownExtra();
            // $html = $Parsedown->text($html);
            // $html = str_replace("img src", "img class=\"img img-rounded img-responsive\" src", $html);

            //$emojis[":yum:"]=json_decode('"\uD83D\uDE00"');
            $emojis[":flu:"]                          = "🤧";
            $emojis[":satisfied:"]                    = "😆";
            $emojis[":sweat_smile:"]                  = "😅";
            $emojis[":joy:"]                          = "😂";
            $emojis[":wink:"]                         = "😉";
            $emojis[":blush:"]                        = "😊";
            $emojis[":innocent:"]                     = "😇";
            $emojis[":heart_eyes:"]                   = "😍";
            $emojis[":kissing_heart:"]                = "😘";
            $emojis[":kissing:"]                      = "😗";
            $emojis[":kissing_closed_eyes:"]          = "😚";
            $emojis[":kissing_smiling_eyes:"]         = "😙";
            $emojis[":yum:"]                          = "😋";
            $emojis[":stuck_out_tongue:"]             = "😛";
            $emojis[":stuck_out_tongue_winking_eye:"] = "😜";
            $emojis[":stuck_out_tongue_closed_eyes:"] = "😝";
            $emojis[":neutral_face:"]                 = "😐";
            $emojis[":expressionless:"]               = "😑";
            $emojis[":no_mouth:"]                     = "😶";
            $emojis[":smirk:"]                        = "😏";
            $emojis[":unamused:"]                     = "😒";
            $emojis[":relieved:"]                     = "😌";
            $emojis[":pensive:"]                      = "😔";
            $emojis[":sleepy:"]                       = "😪";
            $emojis[":sleeping:"]                     = "😴";
            $emojis[":mask:"]                         = "😷";
            $emojis[":dizzy_face:"]                   = "😵";
            $emojis[":sunglasses:"]                   = "😎";
            $emojis[":confused:"]                     = "😕";
            $emojis[":worried:"]                      = "😟";
            $emojis[":open_mouth:"]                   = "😮";
            $emojis[":hushed:"]                       = "😯";
            $emojis[":astonished:"]                   = "😲";
            $emojis[":flushed:"]                      = "😳";
            $emojis[":frowning:"]                     = "😦";
            $emojis[":anguished:"]                    = "😧";
            $emojis[":fearful:"]                      = "😨";
            $emojis[":cold_sweat:"]                   = "😰";
            $emojis[":disappointed_relieved:"]        = "😥";
            $emojis[":cry:"]                          = "😢";
            $emojis[":sob:"]                          = "😭";
            $emojis[":scream:"]                       = "😱";
            $emojis[":confounded:"]                   = "😖";
            $emojis[":persevere:"]                    = "😣";
            $emojis[":disappointed:"]                 = "😞";
            $emojis[":sweat:"]                        = "😓";
            $emojis[":weary:"]                        = "😩";
            $emojis[":tired_face:"]                   = "😫";
            $emojis[":rage:"]                         = "😡";
            $emojis[":pout:"]                         = "😡";
            $emojis[":angry:"]                        = "😠";
            $emojis[":smiling_imp:"]                  = "😈";
            $emojis[":smiley_cat:"]                   = "😺";
            $emojis[":smile_cat:"]                    = "😸";
            $emojis[":joy_cat:"]                      = "😹";
            $emojis[":heart_eyes_cat:"]               = "😻";
            $emojis[":smirk_cat:"]                    = "😼";
            $emojis[":kissing_cat:"]                  = "😽";
            $emojis[":scream_cat:"]                   = "🙀";
            $emojis[":crying_cat_face:"]              = "😿";
            $emojis[":pouting_cat:"]                  = "😾";
            $emojis[":heart:"]                        = "❤️";
            $emojis[":hand:"]                         = "✋";
            $emojis[":raised_hand:"]                  = "✋";
            $emojis[":v:"]                            = "✌️";
            $emojis[":point_up:"]                     = "☝️";
            $emojis[":fist_raised:"]                  = "✊";
            $emojis[":fist:"]                         = "✊";
            $emojis[":monkey_face:"]                  = "🐵";
            $emojis[":cat:"]                          = "🐱";
            $emojis[":cow:"]                          = "🐮";
            $emojis[":mouse:"]                        = "🐭";
            $emojis[":coffee:"]                       = "☕";
            $emojis[":hotsprings:"]                   = "♨️";
            $emojis[":anchor:"]                       = "⚓";
            $emojis[":airplane:"]                     = "✈️";
            $emojis[":hourglass:"]                    = "⌛";
            $emojis[":watch:"]                        = "⌚";
            $emojis[":sunny:"]                        = "☀️";
            $emojis[":star:"]                         = "⭐";
            $emojis[":cloud:"]                        = "☁️";
            $emojis[":umbrella:"]                     = "☔";
            $emojis[":zap:"]                          = "⚡";
            $emojis[":snowflake:"]                    = "❄️";
            $emojis[":sparkles:"]                     = "✨";
            $emojis[":black_joker:"]                  = "🃏";
            $emojis[":mahjong:"]                      = "🀄";
            $emojis[":phone:"]                        = "☎️";
            $emojis[":telephone:"]                    = "☎️";
            $emojis[":envelope:"]                     = "✉️";
            $emojis[":pencil2:"]                      = "✏️";
            $emojis[":black_nib:"]                    = "✒️";
            $emojis[":scissors:"]                     = "✂️";
            $emojis[":wheelchair:"]                   = "♿";
            $emojis[":warning:"]                      = "⚠️";
            $emojis[":aries:"]                        = "♈";
            $emojis[":taurus:"]                       = "♉";
            $emojis[":gemini:"]                       = "♊";
            $emojis[":cancer:"]                       = "♋";
            $emojis[":leo:"]                          = "♌";
            $emojis[":virgo:"]                        = "♍";
            $emojis[":libra:"]                        = "♎";
            $emojis[":scorpius:"]                     = "♏";
            $emojis[":sagittarius:"]                  = "♐";
            $emojis[":capricorn:"]                    = "♑";
            $emojis[":aquarius:"]                     = "♒";
            $emojis[":pisces:"]                       = "♓";
            $emojis[":heavy_multiplication_x:"]       = "✖️";
            $emojis[":heavy_plus_sign:"]              = "➕";
            $emojis[":heavy_minus_sign:"]             = "➖";
            $emojis[":heavy_division_sign:"]          = "➗";
            $emojis[":bangbang:"]                     = "‼️";
            $emojis[":interrobang:"]                  = "⁉️";
            $emojis[":question:"]                     = "❓";
            $emojis[":grey_question:"]                = "❔";
            $emojis[":grey_exclamation:"]             = "❕";
            $emojis[":exclamation:"]                  = "❗";
            $emojis[":heavy_exclamation_mark:"]       = "❗";
            $emojis[":wavy_dash:"]                    = "〰️";
            $emojis[":recycle:"]                      = "♻️";
            $emojis[":white_check_mark:"]             = "✅";
            $emojis[":ballot_box_with_check:"]        = "☑️";
            $emojis[":heavy_check_mark:"]             = "✔️";
            $emojis[":x:"]                            = "❌";
            $emojis[":negative_squared_cross_mark:"]  = "❎";
            $emojis[":curly_loop:"]                   = "➰";
            $emojis[":loop:"]                         = "➿";
            $emojis[":part_alternation_mark:"]        = "〽️";
            $emojis[":eight_spoked_asterisk:"]        = "✳️";
            $emojis[":eight_pointed_black_star:"]     = "✴️";
            $emojis[":sparkle:"]                      = "❇️";
            $emojis[":copyright:"]                    = "©️";
            $emojis[":registered:"]                   = "®️";
            $emojis[":tm:"]                           = "™️";
            $emojis[":brasil:"]                       = "🇧🇷";
            $emojis[":brazil:"]                       = "🇧🇷";

            //$emojis[":yum:"]=json_decode('"u{1F600}"');

            $html = str_replace(array_keys($emojis), array_values($emojis), $html);
        } else {
            $size['title'] = 'h2';
            $size['subTitle'] = 'h4';
            $size['content']  = 'p';

            switch($jarr['size']) {
                case 'small':
                    $size['title'] = 'h3';
                    $size['subTitle'] = 'i';
                    $size['content']  = 'p';
                    break;

                case 'tiny':
                    $size['title'] = 'b';
                    $size['subTitle'] = 'p';
                    $size['content']  = 'small';
                    break;
            }

            if ($jarr['date']) {
                $date = gDateTime($jarr['date']);
            }

            if ($jarr['tags']) {
                $tags = $jarr['tags'];
            }

            if ($jarr['title']) {
                if ($jarr['style']) {
                    $style = " class='text-" . $jarr['style'] . "'";
                }
                $html .= $mai . $size['title'] . "$style" . $men . $jarr['title'] . $mab . $size['title'] . $men . $end;
            }

            if ($jarr['subTitle']) {
                $html .= $mai . $size['subTitle'] . $men . $jarr['subTitle'] . $mab . $size['subTitle'] . $men . $end;
            }

            $add = [];
            if ($date) {
                $add[] = $date;
            }

            if ($tags) {
                $add[] = $tags;
            }

            $html .= "<small>" . implode(" ",$add) . "</small>";
            $html .= $end;
            $html .= $mai . $size['content'] . $men . $content . $mab . $size['content'] . $men;
        }

        return($html);
    }


    public function buttons($buttons): string
    {
        $html = '<div class="btn-group" role="group" aria-label="...">';
        foreach ($buttons as $button) {
            $html .= '<div class="btn-group" role="group">';
            $html .= $button;
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }


    public function text($json, $buttons): string
    {
        $html = '';
        $jarr = cssDecode($json);
        $onClick  = "";
        $valueCss = [];
        $cardCss  = [];

        if ($jarr['style']) {
            $valueCss[] = "text-".$jarr['style'];
        }

        if ($jarr['backgroundStyle']) {
            $cardCss[] = "bg-".$jarr['backgroundStyle'];
        }

        if ($jarr['onClick'] || $jarr['href']) {
            if ($jarr['onClick']) {
                $onClick = "onClick=\"".$jarr['onClick']."\" style=\"cursor: pointer\"";
            } else {
                $onClick = "onClick=\"location='".$jarr['href']."'\" style=\"cursor: pointer\"";
            }
        }

        $html .= "<div class=\"dashcard\" $onClick>";
        $html .= "<div class=\"dashcard-body ".implode(" ",$cardCss)."\">";
        $html .= "<span class=\"dashfont-big ".implode(" ",$valueCss)."\">";
        $html .= $jarr['value'];
        $html .= "</span>";

        if ($jarr['title']) {
            $html .= "<span class=\"dashfont-normal\">";
            $html .= $jarr['title'];
            $html .= "</span>";
        }

        if ($jarr['hint']) {
            $html .= "<span class=\"dashfont-small\">";
            $html .= $jarr['hint'];
            $html .= "</span>";
        }

        $html .= $this->buttons($buttons);
        $html .= "</div>";
        if ($jarr['icon']) {
            $html .= "<div class=\"dashcard-body ".implode(" ",$cardCss)."\">";
            $html .= "<span class=\"fa fa-3x fa-".$jarr['icon']."\"></span>";
            $html .= "</div>";
        }

        $html .= "</div>";
        return $html;
    }


    public function avatar($json): string
    {
        global $gPath, $gBASE, $SITE;

        $imgLocal = "";
        $html = '';
        $jarr = cssDecode($json);
        $onClick  = "";
        $valueCss = [];
        $cardCss  = [];

        if ($jarr['id']) {
            if ($gBASE == "gadmin") {
                $imgLocal = $gPath."files/pessoas/imagem/".$jarr['id'].".jpeg";

                if (file_exists($imgLocal)) {
                    $imgWeb = '<img class="dash-img" src="files/pessoas/imagem/' . $jarr['id'] . '.jpeg?' . random_int(1, 9999) . '">';
                } else {
                    $imgLocal = $gPath."files/pessoas/imagem/".$jarr['id'].".jpg";
                    if (file_exists($imgLocal)) {
                        $imgWeb = '<img class="dash-img" src="files/pessoas/imagem/' . $jarr['id'] . '.jpg?' . random_int(1, 9999) . '">';
                    } else {
                        $imgWeb= '<img class="dash-img" src="files/pessoas/imagem/0.jpg"><!-- '.$imgLocal.' -->';
                    }
                }

            } elseif ($SITE == "gWMS") {
                $imgLocal = $gPath . "files/pessoas/" . $jarr['id'] . ".jpeg";

                if (file_exists($imgLocal)) {
                    $imgWeb = '<img class="dash-img" src="files/pessoas/' . $jarr['id'] . '.jpeg?' . random_int(1, 9999) . '">';
                } else {
                    $imgLocal = $gPath."files/pessoas/".$jarr['id'].".jpg";
                    if (file_exists($imgLocal)) {
                        $imgWeb = '<img class="dash-img" src="files/pessoas/' . $jarr['id'] . '.jpg?' . random_int(1, 9999) . '">';
                    } else {
                        $imgWeb= '<img class="dash-img" src="files/pessoas/0.jpg"><!-- '.$imgLocal.' -->';
                    }
                }

            } else {
                $imgLocal = $gPath."files/geral_pessoas/".$jarr['id'].".jpg";
                if (file_exists($imgLocal)) {
                    $imgWeb = '<img class="dash-img" src="files/geral_pessoas/' . $jarr['id'] . '.jpg?' . random_int(1, 9999) . '">';
                } else {
                    $imgWeb= '<img class="dash-img" src="../../rj/giusoft/files/geral_pessoas/0.jpg"><!-- '.$imgLocal.' -->';
                }

            }
        }

        if ($jarr['style']) {
            $valueCss[] = "text-".$jarr['style'];
        }

        if ($jarr['backgroundStyle']) {
            $cardCss[] = "bg-".$jarr['backgroundStyle'];
        }

        if ($jarr['onClick'] || $jarr['href']) {
            if ($jarr['onClick']) {
                $onClick = "onClick=\"".$jarr['onClick']."\" style=\"cursor: pointer\"";
            } else {
                $onClick = "onClick=\"location='".$jarr['href']."'\" style=\"cursor: pointer\"";
            }
        }

        $html .= "<div class=\"dashcard\" $onClick>";
        if ($imgLocal !== "") {
            $html .= "<div class=\"text-center dashboard-divcol ".implode(" ",$cardCss)."\">";
            $html .= $imgWeb;
            $html .= "<br><br><small>".$jarr['id']."</small>";
            $html .= "</div>";

        }

        $html .= "<div class=\"dashboard-divcol ".implode(" ",$cardCss)."\">";
        $html .= "<div class=\"dashcard-left ".implode(" ",$cardCss)."\">";
        if ($jarr['nickname']) {
            $html .= "<span class=\"dashfont-big ".implode(" ",$valueCss)."\">";
            $html .= ucfirst((string) $jarr['nickname']);
            $html .= "</span>";
        }

        $html .= "<span class=\"dashfont-normal ".implode(" ",$valueCss)."\">";
        $html .= $jarr['name'];
        $html .= "</span>";
        if ($jarr['email'] || $jarr['phone']) {
            $html .= "<span class=\"dashfont-small\">";
            $coisas = [];
            if ($jarr['email']) {
                $coisas[] = $jarr['email'];
            }

            if ($jarr['phone']) {
                $coisas[] = $jarr['phone'];
            }

            $html .= implode("<br>",$coisas);
            $html .= "</span>";
        }

        if ($jarr['hint']) {
            $html .= "<span class=\"dashfont-small\">";
            $html .= $jarr['hint'];
            $html .= "</span>";
        }
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    function chart($json, $dados): string
    {
        global $o;

        include_once $gPath."gfw/inc/gChart.php";

        $html = '';
        $jarr = cssDecode($json);
        $onClick  = "";
        $valueCss = [];
        $cardCss  = [];
        $titleCss = [];

        if ($jarr['style']) {
            $valueCss[] = "text-".$jarr['style'];
        }

        if ($jarr['backgroundStyle']) {
            $cardCss[] = "bg-".$jarr['backgroundStyle'];
        }

        if ($jarr['titleStyle']) {
            $titleCss[] = "bg-".$jarr['titleStyle'];
        }

        if ($jarr['onClick'] || $jarr['href']) {
            if ($jarr['onClick']) {
                $onClick = "onClick=\"".$jarr['onClick']."\" style=\"cursor: pointer\"";
            } else {
                $onClick = "onClick=\"location='".$jarr['href']."'\" style=\"cursor: pointer\"";
            }
        }

        $height = 350;
        if ($jarr['height']) {
            $height = $jarr['height'];
        }

        $chart = new gChart("Line", "100%", $height);
        $chart->setColor('vivid');
        $chart->setParam('xLabelAngle', 60);

        if ($jarr['showLabels']) {
            $chart->setParam('gridTextSize',12);
        } else {
            $chart->setParam('gridTextSize',0);
        }

        $chart->setParam('grid',false);
        $chart->setYlabel($jarr['title']);
        foreach ($dados as $key=>$value) {
            $chart->addAssocXY($key, $value);
        }

        $grafico=$chart->render($o,false);

        $html .= "<div class=\"dashcard-row\" $onClick>";
        $html .= "<div class=\"dashcard-title ".implode(" ",$titleCss)."\">";
        $html .= $jarr['title'];
        $html .= "</div>";
        $html .= "<div class=\"dashcard-body ".implode(" ",$cardCss)."\">";
        $html .= $grafico;
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }


    public function card($json, string $dados): string
    {
        global $o;

        include_once $gPath."gfw/inc/gChart.php";

        $html = '';
        $jarr = cssDecode($json);
        $onClick  = "";
        $valueCss = [];
        $cardCss  = [];
        $titleCss = [];
        if ($jarr['style']) {
            $valueCss[] = "text-".$jarr['style'];
        }

        if ($jarr['backgroundStyle']) {
            $cardCss[] = "bg-".$jarr['backgroundStyle'];
        }

        if ($jarr['titleStyle']) {
            $titleCss[] = "bg-".$jarr['titleStyle'];
        }

        if ($jarr['onClick'] || $jarr['href']) {
            if ($jarr['onClick']) {
                $onClick = "onClick=\"".$jarr['onClick']."\" style=\"cursor: pointer\"";
            } else {
                $onClick = "onClick=\"location='".$jarr['href']."'\" style=\"cursor: pointer\"";
            }
        }

        $height = 350;
        if ($jarr['height']) {
            $height = $jarr['height'];
        }

        $html .= "<div class=\"dashcard-row\" $onClick>";
        if ($jarr['title']) {
            $html .= "<div class=\"dashcard-title ".implode(" ",$titleCss)."\">";
            $html .= $jarr['title'];
            $html .= "</div>";
        }

        $html .= "<div class=\"dashcard-body ".implode(" ",$cardCss)."\">";
        $html .= $dados;
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }


    public function render(): string
    {
        $html = "\n<style type=\"text/css\">
            .dashboard {
                background-color: #f6f6f6;
                padding: 12px;
                border: 1px solid #e0e0e0;
                margin-bottom: 6px;
                border-radius: 7px;
            }
            .dashcard {
                overflow: hidden!important;
                position: relative;
                display: -ms-flexbox;
                display: flex;
                -ms-flex-direction: column;
                flex-direction: row;
                min-width: 0;
                word-wrap: break-word;
                background-color: #fff;
                border: inherit !important;
                background-clip: border-box;
                border-radius: 7px;
                position: relative;
                margin-bottom: 1.5rem;
                width: 100%;
                min-height: 140px;
                box-shadow: 0 4px 25px 0 rgb(168 180 188 / 40%);
            }
            .dashcard-row {
                overflow: hidden!important;
                position: relative;
                display: -ms-flexbox;
                display: flex;
                -ms-flex-direction: column;
                flex-direction: column;
                min-width: 0;
                word-wrap: break-word;
                background-color: #fff;
                border: inherit !important;
                background-clip: border-box;
                border-radius: 7px;
                position: relative;
                margin-bottom: 1.5rem;
                width: 100%;
                min-height: 120px;
                box-shadow: 0 4px 25px 0 rgb(168 180 188 / 40%);
            }
            .dashcard-body {
                -ms-flex: 1 1 auto;
                flex: 1 1 auto;
                margin: 0;
                position: relative;
                padding: 14px;
                box-sizing: border-box;
                display: inline;
                word-wrap: break-word
            }
            .dashcard-title {
                margin: 0;
                text-align: center;
                width: 100%;
                min-height: 32px;
                border-bottom: 1px solid #f0f0f0;
                padding: 0px;
                margin: 0px;
                padding-top: 6px;
                padding-bottom: 6px;
                box-sizing: border-box;
                display: block
            }
            .dashboard-divcol {
                display: inline-box;
                margin: 0;
                position: relative;
                padding: 14px;
                box-sizing: border-box;
                word-wrap: break-word
            }
            .dashfont-big {
                font-size: 250%;
                display: block;
            }
            .dashfont-medium {
                font-size: 120%;
                font-weight: bold
                display: block;
            }
            .dashfont-normal {
                font-size: 100%;
                display: block;
            }
            .dashfont-small {
                font-size: 80%;
                display: block;
            }
            .dash-img {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                display: inline;
                box-shadow: 0 4px 4px 0 rgb(168 180 188 / 40%);
            }

        </style>\n";
        gAddMarkdownRequirements();

        $html .= "\n<div id=\"dashboard\" class=\"dashboard\">\n\n";
        $html .= $this->content;
        $html .= "\n\n</div>\n";

        return $html;
    }
}
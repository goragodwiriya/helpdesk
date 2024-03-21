<?php
/**
 * @filesource modules/helpdesk/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Home;

use Kotchasan\Collection;
use Kotchasan\Http\Request;

/**
 * Controller สำหรับการแสดงผลหน้า Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นสร้าง block
     *
     * @param Request $request
     * @param Collection $block
     * @param array $login
     */
    public static function addBlock($request, $block, $login)
    {
        if ($login) {
            // category
            $category = \Helpdesk\Status\Model::create();
            // get all tickets
            $datas = \Helpdesk\Home\Model::get($login);
            // ทุกคน
            $card = new Collection();
            foreach ($category->toSelect('ticketstatus') as $status => $label) {
                if (!empty($datas[0][$status])) {
                    \Index\Home\Controller::renderCard($card, 'icon-support', $label, number_format($datas[0][$status]), '{LNG_My tickets}', 'index.php?module=helpdesk-history&amp;status='.$status, '', $category->statusColor($status));
                }
            }
            $content = '<article><header><h2 class="icon-dashboard">{LNG_Dashboard}</h2></header>';
            if ($card->count() > 0) {
                $content .= '<div class="ggrid">';
                foreach ($card as $item) {
                    $content .= '<div class="block4 card">'.$item.'</div>';
                }
                $content .= '</div>';
            }
            $content .= '<div class="ggrid">';
            foreach ($category->toSelect('category') as $status => $label) {
                $content .= '<section class="qmenu block4"><a class="cuttext" href="index.php?module=helpdesk-receive&amp;category='.$status.'"><span class="icon-support">{LNG_Create ticket} ('.$label.')</span></a></section>';
            }
            $content .= '</div></article>';
            $block->set('My tickets', $content);
            // เจ้าหน้าที่
            $card = new Collection();
            foreach ($category->toSelect('ticketstatus') as $status => $label) {
                if (!empty($datas[1][$status])) {
                    \Index\Home\Controller::renderCard($card, 'icon-list', $label, number_format($datas[1][$status]), '{LNG_Tickets} ({LNG_Agent})', 'index.php?module=helpdesk-setup&amp;status='.$status, '', $category->statusColor($status));
                }
            }
            if ($card->count() > 0) {
                $content = '<article><header><h2 class="icon-support">{LNG_Helpdesk}</h2></header>';
                $content .= '<div class="ggrid">';
                foreach ($card as $item) {
                    $content .= '<div class="block4 card">'.$item.'</div>';
                }
                $content .= '</div></article>';
                $block->set('Tickets', $content);
            }
        }
    }
}

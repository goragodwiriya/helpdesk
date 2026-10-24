<?php
/**
 * @filesource modules/helpdesk/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Category;

/**
 * หมวดหมู่ สถานะ และความเร่งด่วนของ Ticket
 * ทั้งสามชนิดเก็บอยู่ในตาราง category แยกกันด้วยคอลัมน์ type เหมือนระบบเดิม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ชนิดหมวดหมู่ที่โมดูลนี้ดูแล พร้อมชื่อที่ใช้แสดงผล
     *
     * @var array
     */
    protected static $typies = [
        'ticketstatus' => '{LNG_Status}',
        'ticketpriority' => '{LNG_Priority}',
        'category' => '{LNG_Category}'
    ];

    /**
     * คืนค่าชนิดหมวดหมู่ทั้งหมดที่โมดูลนี้ดูแล
     *
     * @return array
     */
    public static function typies()
    {
        return static::$typies;
    }

    /**
     * ตรวจสอบว่าชนิดที่ส่งมาเป็นของโมดูลนี้หรือไม่ ถ้าไม่ใช่คืนค่าชนิดแรก
     *
     * @param string $type
     *
     * @return string
     */
    public static function validType($type)
    {
        return isset(static::$typies[$type]) ? $type : 'ticketstatus';
    }

    /**
     * ชื่อของชนิดหมวดหมู่ สำหรับใช้เป็นหัวข้อของหน้า
     *
     * @param string $type
     *
     * @return string
     */
    public static function typeLabel($type)
    {
        return isset(static::$typies[$type]) ? static::$typies[$type] : '';
    }

    /**
     * อ่านรายการหมวดหมู่ตามชนิดที่ต้องการ
     *
     * @param string $type
     * @param bool $activeOnly true (ค่าปริยาย) เฉพาะที่เปิดใช้งาน
     *
     * @return array
     */
    public static function all($type, $activeOnly = true)
    {
        $where = [['type', $type]];
        if ($activeOnly) {
            $where[] = ['is_active', 1];
        }

        return static::createQuery()
            ->select('category_id', 'topic', 'color', 'is_active')
            ->from('category')
            ->where($where)
            ->orderBy('category_id')
            ->fetchAll(true);
    }

    /**
     * หมวดหมู่ในรูปแบบ [category_id => ['topic' => .., 'color' => ..]]
     *
     * @param string $type
     * @param bool $activeOnly
     *
     * @return array
     */
    public static function map($type, $activeOnly = true)
    {
        $result = [];
        foreach (self::all($type, $activeOnly) as $item) {
            $result[(int) $item['category_id']] = [
                'topic' => $item['topic'],
                'color' => empty($item['color']) ? '#666666' : $item['color']
            ];
        }

        return $result;
    }

    /**
     * หมวดหมู่สำหรับใส่ลงใน select ของ TableManager/FormManager
     *
     * @param string $type
     * @param bool $activeOnly
     *
     * @return array
     */
    public static function toOptions($type, $activeOnly = true)
    {
        $result = [];
        foreach (self::all($type, $activeOnly) as $item) {
            $result[] = [
                'value' => (int) $item['category_id'],
                'text' => $item['topic']
            ];
        }

        return $result;
    }

    /**
     * ชื่อหมวดหมู่ที่ $id จาก map ที่อ่านมาแล้ว
     *
     * @param array $map ผลจาก map()
     * @param int $id
     *
     * @return string
     */
    public static function topicOf($map, $id)
    {
        return isset($map[(int) $id]) ? $map[(int) $id]['topic'] : '';
    }

    /**
     * สีของหมวดหมู่ที่ $id จาก map ที่อ่านมาแล้ว
     *
     * @param array $map ผลจาก map()
     * @param int $id
     *
     * @return string
     */
    public static function colorOf($map, $id)
    {
        return isset($map[(int) $id]) ? $map[(int) $id]['color'] : '#666666';
    }

    /**
     * นิยามคอลัมน์ของตารางแก้ไขหมวดหมู่ (data-editable-rows)
     * ชนิด category ไม่มีสี จึงไม่แสดงคอลัมน์สี
     *
     * @param string $type
     *
     * @return array
     */
    public static function getColumns($type)
    {
        $columns = [
            [
                'field' => 'category_id',
                'label' => '{LNG_ID}',
                'cellElement' => 'text',
                'size' => 5,
                'class' => 'center',
                'cellClass' => 'center'
            ],
            [
                'field' => 'topic',
                'label' => self::typeLabel($type),
                'cellElement' => 'text',
                'size' => 30
            ]
        ];
        if ($type !== 'category') {
            $columns[] = [
                'field' => 'color',
                'label' => '{LNG_Color}',
                'cellElement' => 'color',
                'size' => 10,
                'class' => 'center',
                'cellClass' => 'center'
            ];
        }
        $columns[] = [
            'field' => 'is_active',
            'label' => '{LNG_Active}',
            'cellElement' => 'switch',
            'class' => 'center',
            'cellClass' => 'center'
        ];

        return $columns;
    }

    /**
     * บันทึกหมวดหมู่ทั้งชนิด (ลบของเดิมแล้วเพิ่มใหม่ตามที่ส่งมา)
     *
     * @param string $type
     * @param array $items
     *
     * @return int จำนวนรายการที่บันทึก
     */
    public static function save($type, $items)
    {
        $db = \Kotchasan\DB::create();
        $db->delete('category', [['type', $type]], 0);

        foreach ($items as $item) {
            $item['type'] = $type;
            $item['language'] = '';
            $db->insert('category', $item);
        }

        return count($items);
    }
}

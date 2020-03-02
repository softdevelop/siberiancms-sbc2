<?php

/**
 * Class Application_Model_Queue
 */
class Application_Model_Queue extends Core_Model_Default {

    public static $TYPES = [
        'ios',
        'iosnoads',
        'android',
        'apk',
    ];

    /**
     * @param $application_id
     * @param $type
     * @param $device
     * @throws Zend_Exception
     */
    public static function cancel($application_id, $type, $device) {
        switch($type) {
            case "apk":
                    $queue = new Application_Model_ApkQueue();
                    $queues = $queue->findAll([
                        "app_id = ?" => $application_id,
                        "status NOT IN (?)" => ["success", "building"],
                    ]);
                    foreach($queues as $queue) {
                        $queue->delete();
                    }
                break;
            case "zip":
                    $queue = new Application_Model_SourceQueue();
                    $queues = $queue->findAll([
                        "app_id = ?" => $application_id,
                        "type = ?" => $device,
                        "status NOT IN (?)" => ["success", "building"],
                    ]);
                    foreach($queues as $queue) {
                        $queue->delete();
                    }
                break;
        }
    }

    /**
     * @param $application_id
     * @return array
     * @throws Zend_Db_Select_Exception
     */
    public static function getPosition($application_id) {
        $db = Zend_Db_Table::getDefaultAdapter();

        $select_source = $db->select()
            ->from("source_queue", [
                "id" => new Zend_Db_Expr("source_queue_id"),
                "type",
                "name",
                "path",
                "app_id",
                "created_at",
                "updated_at",
            ])
            ->where("status IN (?)", ["queued", "building"])
        ;

        $select_apk = $db->select()
            ->from("apk_queue", [
                "id" => new Zend_Db_Expr("apk_queue_id"),
                "type" => new Zend_Db_Expr("'apk'"),
                "name",
                "path",
                "app_id",
                "created_at",
                "updated_at",
            ])
            ->where("status IN (?)", ["queued", "building"])
        ;

        $select = $db
            ->select()
            ->union([
                $select_source,
                $select_apk,
            ])
            ->order("created_at ASC")
        ;

        $results = $db->fetchAll($select);
        $total = sizeof($results);

        $positions = [];
        foreach(self::$TYPES as $type) {
            $positions[$type] = 0;
            $found = false;

            foreach($results as $result) {
                $positions[$type]++;
                if(($result["app_id"] == $application_id) && ($result["type"]  == $type)) {
                    $found = true;
                    break;
                }
            }

            if(!$found) {
                $positions[$type] = 0;
            }
        }

        return [
            "positions" => $positions,
            "total" => $total,
        ];
    }

    /**
     * @return float|int
     * @throws Zend_Db_Select_Exception
     */
    public static function getBuildTime() {
        $db = Zend_Db_Table::getDefaultAdapter();

        $select_source = $db->select()
            ->from("source_queue", [
                "build_time",
            ])
            ->where("status IN (?)", ["success"])
        ;

        $select_apk = $db->select()
            ->from("apk_queue", [
                "build_time",
            ])
            ->where("status IN (?)", ["success"])
        ;

        $source = $db->fetchAll($select_source);
        $apk = $db->fetchAll($select_apk);

        $total = count($source) + count($apk);
        $build_time = 0;
        $build_source = 0;
        $build_apk = 0;

        $build_times = [
            'source' => 0,
            'apk' => 0,
            'global' => 0,
        ];

        foreach($source as $result) {
            $build_source += $result['build_time'];
            $build_time += $result['build_time'];
        }
        foreach($apk as $result) {
            $build_apk += $result['build_time'];
            $build_time += $result['build_time'];
        }

        if((count($source) > 0) && ($build_source > 0)) {
            $build_times['source'] = round($build_source / sizeof($source));
        }

        if((count($apk) > 0) && ($build_apk > 0)) {
            $build_times['apk'] = round($build_apk / sizeof($apk));
        }

        if(($total > 0) && ($build_time > 0)) {
            $build_times['global'] = round($build_time / $total);
        }

        return $build_times;
    }
}

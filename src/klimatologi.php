<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/klimatologi', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $hari = $request->getParam('sampling', date('Y-m-d'));//"2019-06-26");
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));

        // $end = date('Y-m-d', strtotime($hari .' +1day'));
        // $from = "{$hari} 07:00:00";
        // $to = "{$end} 06:55:00";

        $lokasi_daily = $this->db->query("SELECT lokasi.*,
                                            manual_daily.temp_max as temp_max,
                                            manual_daily.temp_min as temp_min,
                                            manual_daily.temp_avg as temp_avg,
                                            manual_daily.humi as humi,
                                            manual_daily.temp_tangki as temp_tangki,
                                            manual_daily.evaporation as evaporation,
                                            manual_daily.wind as wind,
                                            manual_daily.rad as rad,
                                            manual_daily.rain as rain
                                        FROM lokasi
                                        LEFT JOIN manual_daily ON manual_daily.id = (
                                            SELECT id from manual_daily
                                                WHERE lokasi_id = lokasi.id
                                                    AND sampling='{$hari} 07:00:00'
                                                ORDER BY sampling DESC
                                                LIMIT 1
                                        )
                                        WHERE lokasi.jenis='4'")->fetchAll();
        $y_lokasi_daily = $this->db->query("SELECT lokasi.*,
                                            manual_daily.temp_max as temp_max,
                                            manual_daily.temp_min as temp_min,
                                            manual_daily.temp_avg as temp_avg,
                                            manual_daily.humi as humi,
                                            manual_daily.temp_tangki as temp_tangki,
                                            manual_daily.evaporation as evaporation,
                                            manual_daily.wind as wind,
                                            manual_daily.rad as rad,
                                            manual_daily.rain as rain
                                        FROM lokasi
                                        LEFT JOIN manual_daily ON manual_daily.id = (
                                            SELECT id from manual_daily
                                                WHERE lokasi_id = lokasi.id
                                                    AND sampling='{$prev_date} 07:00:00'
                                                ORDER BY sampling DESC
                                                LIMIT 1
                                        )
                                        WHERE lokasi.jenis='4'")->fetchAll();

        return $this->view->render($response, 'klimatologi/index.html', [
            'lokasi_daily' => $lokasi_daily,
            'y_lokasi_daily' => $y_lokasi_daily,
            'sampling' => $hari,
            'yesterday' => tanggal_format(strtotime($prev_date)),
            'today' => tanggal_format(strtotime($hari)),
        ]);
    })->setName('klimatologi');

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            $lokasi_id = $request->getAttribute('id');
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$lokasi_id}")->fetch();

            $current_date = date('Y-m-d');
            $hari = $request->getParam('sampling', $current_date);
            $prev_date = date('Y-m-d', strtotime("{$hari} first day of last month"));
            $next_date = date('Y-m-d', strtotime("{$hari} first day of next month"));
            $bulan = date("m", strtotime($hari));
            $tahun = date("Y", strtotime($hari));

            $end_date = (date('m') == $bulan) ? intval(date('d')) : intval(date("t", strtotime("{$tahun}-{$bulan}")));

            $results = $this->db->query("SELECT *
                                        FROM manual_daily
                                        WHERE lokasi_id = {$lokasi_id}
                                            AND EXTRACT(month FROM sampling) = {$bulan}
                                            AND EXTRACT(year FROM sampling) = {$tahun}
                                        ORDER BY sampling DESC")->fetchAll();

            $manual_daily = [];
            for($i = 1; $i <= $end_date; $i++) {
                $manual_daily[date("Y-m-d", strtotime("{$tahun}-{$bulan}-{$i}"))] = [];
            }
            forEach($results as $r) {
                $manual_daily[date("Y-m-d", strtotime("{$r['sampling']}"))] = $r;
            }
            $manual_daily = array_reverse($manual_daily);
            // dump([$tahun, $bulan]);

            return $this->view->render($response, 'klimatologi/pos.html', [
                'manual_daily' => $manual_daily,
                'lokasi_id' => $lokasi_id,
                'lokasi' => $lokasi,
                'sampling' => $hari,
                'next' => $next_date,
                'prev' => $prev_date
            ]);
        })->setName('klimatologi.pos');

    });

});

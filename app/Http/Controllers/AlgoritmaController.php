<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alternatif;
use App\Models\Kriteria;
use App\Models\Penilaian;
use PDF;
use Carbon\Carbon;

class AlgoritmaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Tampilkan halaman perhitungan.
     */
public function index()
{
    // Ambil kriteria, alternatif, penilaian seperti sebelumnya
    $kriteria = Kriteria::with('crips')
        ->orderByRaw("
            FIELD(nama_kriteria, 
                'Waktu Pelaksanaan', 
                'Biaya Promosi', 
                'Daya Tarik Visual', 
                'Jangkauan Audiens', 
                'Interaksi Konsumen'
            )
        ")
        ->get();

    $alternatif = Alternatif::with('penilaian.crips')->get();
    $penilaian = Penilaian::with('crips','alternatif')->get();

    if ($penilaian->isEmpty()) {
        return redirect(route('penilaian.index'))->with('empty','Data Kosong Silahkan Isi Terlbih Dahulu');
    }

    // 1) Hitung min & max per kriteria (guard bila tidak ada nilai)
    $minMax = [];
    foreach ($kriteria as $k) {
        $vals = [];
        foreach ($penilaian as $p) {
            if ($p->crips->kriteria_id == $k->id) {
                $vals[] = $p->crips->bobot;
            }
        }
        $minMax[$k->id] = [
            'min' => count($vals) ? min($vals) : 0,
            'max' => count($vals) ? max($vals) : 0,
        ];
    }

    // 2) Normalisasi — hitung per alternatif, per kriteria (urut sesuai $kriteria)
    $normalisasi = [];
    foreach ($alternatif as $alt) {
        $altName = $alt->nama_alternatif;
        foreach ($kriteria as $k) {
            // cari nilai raw untuk kriteria ini pada alternatif ini
            $nilai = 0;
            foreach ($alt->penilaian as $p) {
                if ($p->crips->kriteria_id == $k->id) {
                    $nilai = $p->crips->bobot;
                    break;
                }
            }

            if ($k->attribut === 'Benefit') {
                $normalisasi[$altName][$k->id] = $minMax[$k->id]['max'] == 0 ? 0 : ($nilai / $minMax[$k->id]['max']);
            } else { // Cost
                $normalisasi[$altName][$k->id] = $nilai == 0 ? 0 : ($minMax[$k->id]['min'] / $nilai);
            }
        }
        // jaga urutan kriteria agar konsisten
        ksort($normalisasi[$altName]);
    }

    // 3) Hitung nilai berbobot per kriteria (associative) dan total preferensi
    $rankPerAlt = [];    // [altName][kId] = r_ij * bobot
    $preferensi = [];    // [altName] = total Vi
    foreach ($normalisasi as $altName => $vals) {
        $total = 0;
        foreach ($kriteria as $k) {
            $r = isset($vals[$k->id]) ? $vals[$k->id] : 0;
            $score = $r * (float) $k->bobot;
            $rankPerAlt[$altName][$k->id] = $score;
            $total += $score;
        }
        $preferensi[$altName] = $total;
    }

    // 4) Bentuk rankingFull (normalisasi + total) agar mudah ditampilkan
    $rankingFull = [];
    foreach ($normalisasi as $altName => $vals) {
        $rankingFull[$altName] = $vals;
        $rankingFull[$altName]['total'] = $preferensi[$altName] ?? 0;
    }

    // 5) Urutkan berdasarkan total (descending) dan hasilkan sortedData
$sortedData = [];
$rankingSorted = collect($preferensi)
    ->sortDesc()
    ->toArray();

foreach ($rankingSorted as $altName => $total) {
    $sortedData[$altName] = [
        'nilai' => $rankPerAlt[$altName], // nilai weighted per kriteria
        'total' => $total
    ];
}

    // 6) Kembalikan view dengan semua variabel yang mungkin diperlukan
    return view('admin.perhitungan.index', compact(
        'alternatif','kriteria','normalisasi','rankPerAlt','rankingFull','sortedData','preferensi','minMax'
    ));
}

public function downloadPDF()
{
    setlocale(LC_ALL, 'IND');
    $tanggal = Carbon::now()->formatLocalized('%A, %d %B %Y');

    $kriteria = Kriteria::with('crips')
        ->orderByRaw("
            FIELD(nama_kriteria, 
                'Waktu Pelaksanaan', 
                'Biaya Promosi', 
                'Daya Tarik Visual', 
                'Jangkauan Audiens', 
                'Interaksi Konsumen'
            )
        ")
        ->get();

    $alternatif = Alternatif::with('penilaian.crips')->get();
    $penilaian = Penilaian::with('crips','alternatif')->get();

    if ($penilaian->isEmpty()) {
        return redirect(route('penilaian.index'));
    }

    // Cari min/max
    $minMax = [];
    foreach ($kriteria as $k) {
        foreach ($penilaian as $p) {
            if ($k->id == $p->crips->kriteria_id) {
                $minMax[$k->id][] = $p->crips->bobot;
            }
        }
    }

    // Normalisasi
    $normalisasi = [];
    foreach ($penilaian as $p) {
        $altName = $p->alternatif->nama_alternatif;
        $kId = $p->crips->kriteria_id;
        $nilai = $p->crips->bobot;

        if (!isset($minMax[$kId]) || count($minMax[$kId]) == 0) continue;

        $attrib = null;
        foreach ($kriteria as $k) {
            if ($k->id == $kId) {
                $attrib = $k->attribut;
                break;
            }
        }

        if ($attrib === 'Benefit') {
            $normalisasi[$altName][$kId] = max($minMax[$kId]) == 0 ? 0 : ($nilai / max($minMax[$kId]));
        } else {
            $normalisasi[$altName][$kId] = $nilai == 0 ? 0 : (min($minMax[$kId]) / $nilai);
        }
    }

    // pastikan semua key ada
    foreach ($normalisasi as $altName => &$vals) {
        foreach ($kriteria as $k) {
            if (!isset($vals[$k->id])) $vals[$k->id] = 0;
        }
        ksort($vals);
    }
    unset($vals);

    // Perangkingan per alternatif (nilai*bobot per kriteria)
    $rankPerAlt = [];
    foreach ($normalisasi as $altName => $vals) {
        $rankPerAlt[$altName] = [];
        foreach ($kriteria as $k) {
            $r = isset($vals[$k->id]) ? $vals[$k->id] : 0;
            $rankPerAlt[$altName][] = $r * (float)$k->bobot;
        }
    }

    // Bentuk rankingFull = normalisasi + total
    $rankingFull = $normalisasi;
    foreach ($rankingFull as $altName => $vals) {
        $total = array_sum($rankPerAlt[$altName]);
        $rankingFull[$altName][] = $total;
    }

    $sortedData = collect($rankingFull)->sortByDesc(function($value) {
        return array_sum($value);
    })->toArray();

    $pdf = PDF::loadView('admin.perhitungan.perhitungan-pdf', compact('alternatif','kriteria','normalisasi','sortedData','tanggal'));
    $pdf->setPaper('A3', 'potrait');
    return $pdf->stream('perhitungan.pdf');
}

}
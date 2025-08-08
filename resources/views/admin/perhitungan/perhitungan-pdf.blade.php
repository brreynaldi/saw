@php
    // Ambil path file gambar di public/images
    $path = public_path('images/1.jpeg');

    // Pastikan file ada sebelum diubah ke base64
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
    } else {
        $base64Logo = '';
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <style type="text/css">
        .garis1 {
            border-top:3px solid black;
            height: 2px;
            border-bottom:1px solid black;
        }
        #camat {
            text-align:center;
        }
        #nama-camat {
            margin-top:100px;
            text-align:center;
        }
        #ttd {
            position: absolute;
            bottom: 10;
            right: 20;
        }
        .logo-rounded {
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div>
        <table>
            <tr>
                <td style="padding-right: 50px; padding-left: 200px;">
                    @if($base64Logo)
                        <img src="{{ $base64Logo }}" alt="Logo" width="100" height="100" class="logo-rounded">
                    @else
                        <small>Logo tidak ditemukan</small>
                    @endif
                </td>
                <td>
                    <center>
                        <font size="4">OPTIMALISASI STRATEGI PROMOSI</font><br>
                        <font size="4">WARUNG KOPI 6 SAUDARA</font><br>
                        <font size="2">KECAMATAN DUREN SAWIT</font><br>
                        <font size="2">SIMPLE ADDITIVE WEIGHTING (SAW)</font><br>
                    </center>
                </td>
                
            </tr>
        </table>          

        <hr class="garis1"/>
        <div style="margin-top: 25px; margin-bottom: 25px;">
            <center><strong><u>Hasil Perankingan</u></strong></center>
        </div>

        <div class="collapse show" id="rank">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                @foreach ($kriteria as $key => $value)
                                    <th>{{ $value->nama_kriteria }}</th>
                                @endforeach
                                <th rowspan="2" style="text-align: center; padding-bottom: 45px">Total</th>
                                <th rowspan="2" style="text-align: center; padding-bottom: 45px">Rank</th>
                            </tr>
                            <tr>
                                <th>Nama / Bobot</th>
                                @foreach ($kriteria as $key => $value)
                                    <th>{{ $value->bobot }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach($sortedData as $key => $value)
                                <tr>
                                    <td>{{ $key }}</td>
                                    @foreach($value as $key_1 => $value_1)
                                        <td>{{ number_format($value_1, 2) }}</td>
                                    @endforeach
                                    <td>{{ $no++ }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="ttd" class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <p id="camat">Duren Sawit, {{ $tanggal }}</p>
                <p id="camat"><strong>OWNER</strong></p>
                <div id="nama-camat"><strong><u>NAMA OWNER</u></strong><br />
            </div>
        </div>
    </div>
</body>
</html>

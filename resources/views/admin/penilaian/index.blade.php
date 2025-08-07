@extends('layouts.app')
@section('title', 'SPK Penerima Bantuan')
@section('topbar', 'Data Penilaian')
@section('content')

<div class="card shadow mb-4">
    <!-- Card Header - Accordion -->
    <a href="#listkriteria" class="d-block card-header py-3" data-toggle="collapse"
       role="button" aria-expanded="true" aria-controls="collapseCardExample">
        <h6 class="m-0 font-weight-bold text-primary">Penilaian Alternatif</h6>
    </a>

    <!-- Card Content - Collapse -->
    <div class="collapse show" id="listkriteria">
        <div class="card-body">
            @if (Session::has('msg'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>Info</strong> {{ Session::get('msg') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('empty'))
                <div class="alert alert-danger">
                    {{ session('empty') }}
                </div>
            @endif

            <div class="table-responsive">
                <form action="{{ route('penilaian.store')}}" method="post">
                    @csrf
                    <div class="float-right">
                        <button class="btn btn-sm btn-primary">Simpan</button>
                    </div>
                    <br><br>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Alternatif</th>
                                @foreach ($kriteria as $key => $value)
                                    <th>{{ $value->nama_kriteria }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($alternatif as $valt)
    <tr>
        <td>{{ $valt->nama_alternatif }}</td>
        @php
            $selectedCrips = [];
            foreach($valt->penilaian as $pen){
                if($pen->crips && $pen->crips->kriteria_id){
                    $selectedCrips[$pen->crips->kriteria_id] = $pen->crips_id;
                }
            }
        @endphp

        @foreach($kriteria as $value)
            <td>
                <select name="crips_id[{{$valt->id}}][]" class="form-control">
                    @foreach($value->crips as $v_1)
                        <option value="{{ $v_1->id }}"
                            {{ isset($selectedCrips[$value->id]) && $selectedCrips[$value->id] == $v_1->id ? 'selected' : '' }}>
                            {{ $v_1->nama_crips }}
                        </option>
                    @endforeach
                </select>
            </td>
        @endforeach
    </tr>
@endforeach
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

@stop

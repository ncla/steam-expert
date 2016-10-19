@extends('layouts.app')

@section('contentheader_title')
    Overview
@endsection

@section('main-content')
    <div class="row">
        @foreach(\App\ComponentState::getAll() as $i => $component)
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-{{ ['blue', 'green', 'yellow', 'red'][($i + floor($i / 4)) % 4] }}">
                    <span class="info-box-icon">
                        <i class="fa fa-{{ isset($component->icon) ? $component->icon : 'calendar' }}"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $component->name }}</span>
                        <span class="info-box-number {{ str_replace(' ', '_', strtoupper($component->name)) }}">{{ $component->state }}</span>
                    <span class="progress-description">
                      Last update: {{ $component->updated_at }}
                    </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            inc_time();
        });

        function inc_time() {
            var now = new Date;
            document.getElementsByClassName('SYSTEM_TIME')[0].innerHTML =
                    now.getUTCHours() + ':' + padTime(now.getUTCMinutes()) + ':' + padTime(now.getUTCSeconds());
            setTimeout(inc_time, 1000);
        }
        function padTime(i) {
            if (i < 10) i = "0" + i;
            return i;
        }
    </script>
@endsection

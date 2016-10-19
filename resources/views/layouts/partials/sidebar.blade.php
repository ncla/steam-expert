<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel (optional) -->
        @if (! Auth::guest())
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="{{ Auth::user()->avatar }}" class="img-circle" alt="User Image" />
                </div>
                <div class="pull-left info">
                    <p>{{ Auth::user()->name }}</p>
                    <!-- Status -->
                    <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
                </div>
            </div>
        @endif

        <!-- search form (Optional) -->
        <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..."/>
              <span class="input-group-btn">
                <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i></button>
              </span>
            </div>
        </form>
        <!-- /.search form -->
        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="header">HEADER</li>
            <!-- Optionally, you can add icons to the links -->
            <li class="{{ Request::is('admin') ? 'active' : '' }}"><a href="{{ url('admin') }}"><i class='fa fa-link'></i> <span>Home</span></a></li>
            <li class="treeview">
                <a href="#"><i class='fa fa-link'></i> <span>Perflogs</span> <i class="fa fa-angle-left pull-right"></i></a>
                <ul class="treeview-menu perflogs">
                    @foreach(\App\PerformanceLog::getStats() as $stat)
                    <li><a href="/admin/perflog/{{$stat}}">{{$stat}}</a></li>
                    @endforeach
                </ul>
            </li>
            <li class="treeview">
                <a href="#"><i class='fa fa-link'></i> <span>Odditems</span> <i class="fa fa-angle-left pull-right"></i></a>
                <ul class="treeview-menu odditems">
                    @foreach(\App\Admin\OddItems::getList() as $stat)
                        <li><a href="/admin/odditems/{{$stat}}">{{$stat}}</a></li>
                    @endforeach
                </ul>
            </li>
            <li class="treeview">
                <a href="#"><i class='fa fa-link'></i> <span>Comparisons</span> <i class="fa fa-angle-left pull-right"></i></a>
                <ul class="treeview-menu comparisons">
                    @foreach(\App\Admin\Comparisons::getList() as $stat)
                    <li><a href="/admin/comparisons/{{$stat}}">{{$stat}}</a></li>
                    @endforeach
                </ul>
            </li>
        </ul><!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>

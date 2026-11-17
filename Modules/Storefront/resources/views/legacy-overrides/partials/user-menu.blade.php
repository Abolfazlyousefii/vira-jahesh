<ul class="nav float-left">
    <li class="nav-item account dropdown">
        <a class="nav-link" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="label-dropdown">حساب کاربری</span>
            <i class="mdi mdi-account-circle-outline"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-sm dropdown-menu-left">
            @auth
                <a class="dropdown-item" href="{{ route('workspace.select') }}">محیط کاری ویرا</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item" type="submit">خروج از حساب</button>
                </form>
            @else
                <a class="dropdown-item" href="{{ route('login') }}">ورود به ویرا</a>
            @endauth
        </div>
    </li>
</ul>

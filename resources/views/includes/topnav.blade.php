<nav class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 px-4 xl:px-6 py-2.5 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
   <div class="flex flex-wrap justify-between items-center">
        <div class="flex shrink-0 justify-start items-center">
            @auth
                <a class="navbar-brand" href="{{ url('/dashboard') }}">
                    <x-logo/>
                </a>
            @else
                <a class="navbar-brand" href="{{ url('/') }}">
                    <x-logo/>
                </a>
            @endauth
        </div>
        <div class="hidden justify-between items-center w-full lg:flex lg:w-auto lg:order-1" id="mobile-menu-2">
        
            {{-- <button data-collapse-toggle="mega-menu-full" type="button"
                    class="inline-flex items-center p-2 ml-1 text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-hidden focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                    aria-controls="mega-menu-full" aria-expanded="false">
                <span class="sr-only">Hauptmenü</span>
                <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                     xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd"
                          d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                          clip-rule="evenodd"></path>
                </svg>
            </button>

            <div id="mega-menu-full" class="hidden justify-between items-center w-full md:flex md:w-auto md:order-1"> --}}
            <!-- Left Side Of Navbar -->
                 <ul class="flex flex-col font-medium lg:flex-row lg:space-x-8">
                    @auth
                        <x-navbar-admin/>
                        <x-navbar-manager/>
                        <x-navbar-helper/>
                    @endauth
                </ul>
        </div>
        <div class="flex shrink-0 justify-between items-center ml-4 lg:order-2">
                <!-- Right Side Of Navbar -->
                    <!-- Authentication Links -->
            <x-navbar-camp/>
            <x-navbar-user/>
            <x-toggle-switch/>
            <button data-collapse-toggle="mobile-menu-2" type="button" class="inline-flex items-center p-2 ml-1 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-hidden focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="mobile-menu-2" aria-expanded="false">
                <span class="sr-only">Hauptmenü öffnen</span>
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                <svg class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
    </div>
</nav>

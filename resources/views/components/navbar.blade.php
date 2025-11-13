<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <div class="flex-shrink-0">
                <a href="/" class="text-xl font-bold text-gray-800">
                    ABDGROUP
                </a>
            </div>
            
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                
                <a href="/" 
                   class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                          {{ request()->is('/') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Home
                </a>
                
                <a href="/about" 
                   class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                          {{ request()->is('about*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    About Us
                </a>

                <a href="/news" 
                   class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                          {{ request()->is('news*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    News
                </a>

                <a href="/contact" 
                   class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                          {{ request()->is('contact*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Contact
                </a>

                <a href="/career" 
                   class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                          {{ request()->is('career*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Career
                </a>

                <a href="/support" 
                   class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                          {{ request()->is('support*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Support
                </a>
            </div>

            <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
                <a href="{{ route('filament.abdgroup.auth.login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                    Sign In
                </a>
                <a href="/e-procurement" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                    E-Procurement
                </a>
            </div>

            </div>
    </div>
</nav>
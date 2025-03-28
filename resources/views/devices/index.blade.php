<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      {{ __('登録機器') }}
    </h2>
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="flex justify-left">
        <a href="{{ route('devices.create', $devices) }}" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
          新規登録
        </a>
      </div>
    </div>
  </div>
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          <div class="overflow-x-auto relative">
            <div class="inline-block min-w-full">
              <div class="overflow-hidden">
                <table class="min-w-full table-auto md:w-full">
                  <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                      <th scope="col" class="w-1/3 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">発電セット名</th>
                      <th scope="col" class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">メーカー</th>
                      <th scope="col" class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">型番</th>
                      <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($devices as $device)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <td class="w-1/3 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->device_name }}</td>
                      <td class="w-1/4 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->facility_maker }}</td>
                      <td class="w-1/4 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->facility_name }}</td>
                      <td class="w-1/6 px-6 py-4 text-sm">
                        <a href="{{ route('devices.show', $device) }}" class="text-blue-500 hover:text-blue-700">詳細を見る</a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>

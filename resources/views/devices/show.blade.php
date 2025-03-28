<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      {{ __('登録機器 詳細') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          <a href="{{ route('devices.index') }}" class="text-blue-500 hover:text-blue-700 mr-2 mb-4 inline-block">一覧に戻る</a>
          
          <div class="overflow-x-auto relative mt-4">
            <div class="inline-block min-w-full">
              <div class="overflow-hidden">
                <table class="min-w-full table-auto md:w-full">
                  <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <th scope="row" class="w-1/4 px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-300">発電セット名</th>
                      <td class="w-3/4 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->device_name }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <th scope="row" class="w-1/4 px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-300">メーカー</th>
                      <td class="w-3/4 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->facility_maker }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <th scope="row" class="w-1/4 px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-300">型番</th>
                      <td class="w-3/4 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->facility_name }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <th scope="row" class="w-1/4 px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-300">登録日</th>
                      <td class="w-3/4 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <th scope="row" class="w-1/4 px-6 py-4 text-sm font-medium text-gray-500 dark:text-gray-300">更新日</th>
                      <td class="w-3/4 px-6 py-4 text-sm text-gray-800 dark:text-gray-300">{{ $device->updated_at->format('Y-m-d H:i') }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          @if (auth()->id() == $device->user_id)
          <div class="flex mt-6">
            <a href="{{ route('devices.edit', $device) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">編集</a>
            <form action="{{ route('devices.destroy', $device) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
              @csrf
              @method('DELETE')
              <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">削除</button>
            </form>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-app-layout>

<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      {{ __('登録機器情報 編集') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          <a href="{{ route('devices.show', $device) }}" class="text-blue-500 hover:text-blue-700 mr-2">詳細に戻る</a>
          <form method="POST" action="{{ route('devices.update', $device) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
              <label for="device_name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">発電セット名</label>
              <input type="text" name="device_name" id="device_name" value="{{ $device->device_name }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline">
              @error('device_name')
                <span class="text-red-500 text-xs italic">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-4">
              <label for="facility_maker" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">メーカー</label>
              <input type="text" name="facility_maker" id="facility_maker" value="{{ $device->facility_maker }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline">
              @error('facility_maker')
                <span class="text-red-500 text-xs italic">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-4">
              <label for="facility_name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">型番</label>
              <input type="text" name="facility_name" id="facility_name" value="{{ $device->facility_name }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline">
              @error('facility_name')
                <span class="text-red-500 text-xs italic">{{ $message }}</span>
              @enderror
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Update</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>

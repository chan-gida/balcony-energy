<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      {{ __('Sensors Information') }}
    </h2>
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="flex justify-left">
        <a href="{{ route('devices.create', $devices) }}" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
          Create New ID
        </a>
      </div>
    </div>
  </div>
  
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          @foreach ($devices as $device)
          <div class="mb-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <p class="text-gray-800 dark:text-gray-300">Sensor Name：{{ $device->device_name }}</p>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Sensor ID: {{ $device->sensor_id }}</p>
            <a href="{{ route('devices.show', $device) }}" class="text-blue-500 hover:text-blue-700">詳細を見る</a>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

</x-app-layout>

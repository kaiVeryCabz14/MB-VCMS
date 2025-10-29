@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-teal-50 to-cyan-50 p-4 sm:p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center"><i class="fas fa-hotel text-teal-600 mr-2"></i> Pet Boarding</h2>
            <a href="{{ route('medical.index', ['tab' => 'boarding']) }}" 
               class="px-4 py-2 bg-gray-200 border-2 border-gray-300 rounded-lg hover:bg-gray-300 font-medium shadow-sm transition">← Back</a>
        </div>
        <div class="space-y-6">

            {{-- Row 2: Pet Info (left) + Recent History (right) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openPetProfileModal()">
                    <div class="font-semibold text-gray-900 mb-1">{{ $visit->pet->pet_name ?? 'Pet' }} <span class="text-gray-500">({{ $visit->pet->pet_species ?? '—' }})</span></div>
                    <div class="text-sm text-gray-700">Owner: <span class="font-medium">{{ $visit->pet->owner->own_name ?? '—' }}</span></div>
                    <div class="text-xs text-gray-500 mt-1">Breed: {{ $visit->pet->pet_breed ?? '—' }}</div>
                    <div class="text-xs text-gray-500">Weight: {{ $visit->weight ? number_format($visit->weight, 2).' kg' : '—' }} • Temp: {{ $visit->temperature ? number_format($visit->temperature, 1).' °C' : '—' }}</div>
                    <div class="mt-3 inline-flex items-center gap-2 text-indigo-600 text-sm font-medium">View Full Profile <i class="fas fa-arrow-right"></i></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openMedicalHistoryModal()">
                    <div class="font-semibold text-gray-900 mb-2">Recent Medical History</div>
                    <div class="space-y-2 max-h-40 overflow-y-auto text-xs">
                        @forelse($petMedicalHistory as $record)
                            <div class="border-l-2 pl-2 {{ $record->diagnosis ? 'border-red-400' : 'border-gray-300' }}">
                                <div class="font-medium">{{ \Carbon\Carbon::parse($record->visit_date)->format('M j, Y') }}</div>
                                <div class="text-gray-700 truncate">{{ $record->diagnosis ?? $record->treatment ?? 'Routine Visit' }}</div>
                            </div>
                        @empty
                            <p class="text-gray-500 italic">No history</p>
                        @endforelse
                    </div>
                    <div class="mt-3 inline-flex items-center gap-2 text-indigo-600 text-sm font-medium">View Full History <i class="fas fa-arrow-right"></i></div>
                </div>
            </div>

            {{-- Row 3+: Main Content (full width) --}}
            <div class="space-y-6">
                @php
            $__details = json_decode($visit->details_json ?? '[]', true) ?: [];
            $__board = [];
            if (isset($serviceData) && $serviceData) {
                $__board = [
                    'checkin' => $serviceData->check_in_date ? \Carbon\Carbon::parse($serviceData->check_in_date)->format('Y-m-d\\TH:i') : null,
                    'checkout' => $serviceData->check_out_date ? \Carbon\Carbon::parse($serviceData->check_out_date)->format('Y-m-d\TH:i') : null,
                    'room' => $serviceData->room_no ?? null,
                    'care_instructions' => $serviceData->feeding_schedule ?? null,
                    'monitoring_notes' => $serviceData->daily_notes ?? null,
                    'billing_basis' => $__details['billing_basis'] ?? null,
                    'rate' => $__details['rate'] ?? null,
                    'total_days' => $__details['total_days'] ?? null,
                    'weight' => $visit->weight,
                ];
            }
        @endphp
        <form action="{{ route('medical.visits.boarding.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            {{-- Boarding Details Card --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-calendar-alt mr-2 text-teal-600"></i> Reservation Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Check-in Date/Time <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="checkin" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" required value="{{ old('checkin', $__board['checkin'] ?? ($__details['checkin'] ?? '')) }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Check-out Date/Time</label>
                        <input type="datetime-local" name="checkout" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" value="{{ old('checkout', $__board['checkout'] ?? ($__details['checkout'] ?? '')) }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Cage / Room</label>
                        <input type="text" name="room" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" placeholder="e.g. C-12" value="{{ old('room', $__board['room'] ?? ($__details['room'] ?? '')) }}" />
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Feeding & Care Instructions</label>
                        <textarea name="care_instructions" rows="3" class="w-full border border-gray-300 p-3 rounded-lg focus:border-teal-500 focus:ring-teal-500" placeholder="Diet, meds times, play time requests...">{{ old('care_instructions', $__board['care_instructions'] ?? ($__details['care_instructions'] ?? '')) }}</textarea>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Monitoring Notes / Daily Logs</label>
                        <textarea name="monitoring_notes" rows="3" class="w-full border border-gray-300 p-3 rounded-lg focus:border-teal-500 focus:ring-teal-500" placeholder="Daily observations (appetite, mood, eliminations)...">{{ old('monitoring_notes', $__board['monitoring_notes'] ?? ($__details['monitoring_notes'] ?? '')) }}</textarea>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-4 flex items-center border-t pt-4"><i class="fas fa-calculator mr-2 text-blue-600"></i> Billing Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Billing Basis</label>
                        <select name="billing_basis" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500">
                            @php($bb = old('billing_basis', $__board['billing_basis'] ?? ($__details['billing_basis'] ?? 'day')))
                            <option value="day" {{ $bb==='day'?'selected':'' }}>Per Day</option>
                            <option value="hour" {{ $bb==='hour'?'selected':'' }}>Per Hour</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Rate (Per basis)</label>
                        <input type="number" step="0.01" name="rate" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" value="{{ old('rate', $__board['rate'] ?? ($__details['rate'] ?? '')) }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Total Days / Hours</label>
                        <input type="number" step="0.1" name="total_days" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" placeholder="Auto-calculated (optional)" value="{{ old('total_days', $__board['total_days'] ?? ($__details['total_days'] ?? '')) }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Pet Weight (kg)</label>
                        <input type="text" readonly class="w-full border border-gray-300 p-2 rounded-lg bg-gray-100 text-gray-600" value="{{ $visit->weight ? number_format($visit->weight, 2) . ' kg' : 'N/A' }}" />
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-semibold shadow-md transition">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Compute & Bill
                </button>
                <a href="{{ route('medical.index', ['tab' => 'boarding']) }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                    <i class="fas fa-save mr-1"></i> Save Record
                </button>
            </div>

            <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
            <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
        </form>
            </div>
        </div>
    </div>
</div>

<!-- Pet Profile Modal (Photo + Pet & Owner Info Only) -->
<div id="petProfileModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closePetProfileModal()}">
    <div class="bg-white rounded-xl shadow-2xl w-[600px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800">Pet Profile</h3>
        <button type="button" onclick="closePetProfileModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-6 space-y-4">
        <!-- Pet Photo -->
        <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
          @if(!empty($visit->pet->pet_photo))
            <img src="{{ asset('storage/'.$visit->pet->pet_photo) }}" alt="{{ $visit->pet->pet_name ?? 'Pet' }}" class="w-full h-80 object-cover"/>
          @else
            <div class="h-80 w-full flex items-center justify-center text-gray-400 text-lg">
              <i class="fas fa-paw text-6xl"></i>
            </div>
          @endif
        </div>

        <!-- Pet Information -->
        <div class="bg-white rounded-lg border p-4">
          <div class="font-semibold text-gray-800 text-lg mb-3 flex items-center gap-2">
            <i class="fas fa-dog text-blue-600"></i> Pet Information
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-gray-500">Name:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_name ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Species:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_species ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Breed:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_breed ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Gender:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_gender ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Age:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_age ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Weight:</span>
              <div class="font-medium text-gray-800">{{ $visit->weight ? number_format($visit->weight, 2).' kg' : '—' }}</div>
            </div>
            <div class="col-span-2">
              <span class="text-gray-500">Temperature:</span>
              <div class="font-medium text-gray-800">{{ $visit->temperature ? number_format($visit->temperature, 1).' °C' : '—' }}</div>
            </div>
          </div>
        </div>

        <!-- Owner Information -->
        <div class="bg-white rounded-lg border p-4">
          <div class="font-semibold text-gray-800 text-lg mb-3 flex items-center gap-2">
            <i class="fas fa-user text-green-600"></i> Owner Information
          </div>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-gray-500">Name:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_name ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Contact:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_contactnum ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Location:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_location ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Medical History Modal (History Only) -->
<div id="medicalHistoryModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closeMedicalHistoryModal()}">
    <div class="bg-white rounded-xl shadow-2xl w-[900px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
          <i class="fas fa-history text-orange-600"></i> 
          Complete Medical History - {{ $visit->pet->pet_name ?? 'Pet' }}
        </h3>
        <button type="button" onclick="closeMedicalHistoryModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-6">
        <div class="space-y-4 max-h-[75vh] overflow-y-auto">
          @forelse($petMedicalHistory as $record)
            <div class="border-l-4 pl-4 py-3 {{ $record->diagnosis ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }} rounded-r-lg">
              <div class="flex items-center justify-between mb-2">
                <div class="font-semibold text-gray-800 text-base">
                  {{ \Carbon\Carbon::parse($record->visit_date)->format('F j, Y') }}
                </div>
                @if(!empty($record->service_type))
                  <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">{{ $record->service_type }}</span>
                @endif
              </div>
              
              @if($record->diagnosis)
                <div class="mb-2">
                  <span class="text-xs font-semibold text-gray-600">Diagnosis:</span>
                  <div class="text-sm text-gray-800">{{ $record->diagnosis }}</div>
                </div>
              @endif

              @if($record->treatment)
                <div class="mb-2">
                  <span class="text-xs font-semibold text-gray-600">Treatment:</span>
                  <div class="text-sm text-gray-800">{{ $record->treatment }}</div>
                </div>
              @endif

              @if($record->medication)
                <div class="mb-2">
                  <span class="text-xs font-semibold text-gray-600">Medication:</span>
                  <div class="text-sm text-blue-700">{{ $record->medication }}</div>
                </div>
              @endif

              @if(!$record->diagnosis && !$record->treatment)
                <div class="text-sm text-gray-600 italic">Routine Visit</div>
              @endif
            </div>
          @empty
            <div class="text-center py-8">
              <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-3"></i>
              <p class="text-gray-500 italic">No medical history on record.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function openPetProfileModal() { 
    const m = document.getElementById('petProfileModal'); 
    if(m){ m.classList.remove('hidden'); } 
  }
  
  function closePetProfileModal() { 
    const m = document.getElementById('petProfileModal'); 
    if(m){ m.classList.add('hidden'); } 
  }

  function openMedicalHistoryModal() { 
    const m = document.getElementById('medicalHistoryModal'); 
    if(m){ m.classList.remove('hidden'); } 
  }
  
  function closeMedicalHistoryModal() { 
    const m = document.getElementById('medicalHistoryModal'); 
    if(m){ m.classList.add('hidden'); } 
  }
</script>

@endsection
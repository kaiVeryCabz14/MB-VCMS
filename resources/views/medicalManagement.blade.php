@extends('AdminBoard')
@php
    $userRole = strtolower(auth()->user()->user_role ?? '');
    
    // Define permissions for each role
    $permissions = [
        'superadmin' => [
            // Appointments
            'view_appointments' => true,
            'add_appointment' => false,
            'edit_appointment' => false,
            'delete_appointment' => false,
            'prescribe_appointment' => false,
            'refer_appointment' => false,
            
            // Prescriptions
            'view_prescriptions' => true,
            'add_prescription' => false,
            'edit_prescription' => false,
            'delete_prescription' => true,
            'print_prescription' => true,
            
            // Referrals
            'view_referrals' => true,
            'add_referral' => false,
            'edit_referral' => false,
            'delete_referral' => false,
            'print_referral' => true,
        ],
        'veterinarian' => [
            // Appointments
            'view_appointments' => true,
            'add_appointment' => false,
            'edit_appointment' => true,
            'delete_appointment' => false,
            'prescribe_appointment' => true,
            'refer_appointment' => true,
            
            // Prescriptions - FULL ACCESS
            'view_prescriptions' => true,
            'add_prescription' => true,
            'edit_prescription' => true,
            'delete_prescription' => true,
            'print_prescription' => true,
            
            // Referrals - FULL ACCESS
            'view_referrals' => true,
            'add_referral' => true,
            'edit_referral' => true,
            'delete_referral' => true,
            'print_referral' => true,

            'view_vaccinations' => true,
            'edit_vaccinations' => true,
        ],
        'receptionist' => [
            // Appointments
            'view_appointments' => true,
            'add_appointment' => true,
            'edit_appointment' => true,
            'delete_appointment' => true,
            'prescribe_appointment' => false,
            'refer_appointment' => false,
            
            // Prescriptions - VIEW AND PRINT ONLY
            'view_prescriptions' => true,
            'add_prescription' => false,
            'edit_prescription' => false,
            'delete_prescription' => false,
            'print_prescription' => true,
            
            // Referrals - VIEW AND PRINT ONLY
            'view_referrals' => true,
            'add_referral' => false,
            'edit_referral' => false,
            'delete_referral' => false,
            'print_referral' => true,
        ],
    ];
    
    // Get permissions for current user
    $can = $permissions[$userRole] ?? $permissions['receptionist'];
    
    // Helper function to check permission
    function hasPermission($permission, $can) {
        return $can[$permission] ?? false;
    }
@endphp

@section('content')
<div class="min-h-screen">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        
        {{-- Tab Navigation --}}
<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8 items-center">
        <button onclick="showTab('visits')" id="visits-tab" 
            class="tab-button py-2 px-1 border-b-2 font-medium text-sm active">
            <h2 class="font-bold text-xl">Visits</h2>
        </button>
        @if(!empty($selectedServiceTabs))
            <span class="mx-2 h-6 w-px bg-gray-300"></span>
            @foreach($selectedServiceTabs as $tab)
                <a href="{{ $tab['url'] }}" class="py-2 px-3 border-b-2 border-transparent text-sm font-medium text-gray-600 hover:text-gray-900 hover:border-gray-300">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        @endif
    </nav>
</div>


        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-500 text-white p-2 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif
        <!-- ==================== VISITS TAB ==================== -->
        <div id="visitsContent" class="tab-content">
            <!-- Controls -->
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <form method="GET" action="{{ route('medical.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="active_tab" value="visits">
                    <label for="visitPerPage" class="text-sm text-black">Show</label>
                    <select name="visitPerPage" id="visitPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('visitPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
                @if(auth()->check() && in_array(auth()->user()->user_role, ['receptionist']))
                <button onclick="openAddVisitModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">+ Add Visit</button>
                @endif
            </div>
            <br>

            <!-- Visits Table -->
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Weight</th>
                            <th class="border px-4 py-2">Temp</th>
                            <th class="border px-4 py-2">Patient Type</th>
                            <th class="border px-4 py-2">Service Type</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($visits ?? []) as $index => $visit)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($visits, 'firstItem'))
                                        {{ $visits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($visit->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">{{ $visit->pet->pet_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $visit->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $visit->weight ? number_format($visit->weight, 2) . ' kg' : 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $visit->temperature ? number_format($visit->temperature, 1) . ' °C' : 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $visit->patient_type }}</td>
                                <td class="border px-4 py-2">{{ $visit->service_type ?? '-' }}</td>
                                <td class="border px-4 py-2">{{ $visit->visit_status ?? '-' }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <a href="{{ route('medical.index', ['visit_id' => $visit->visit_id, 'active_tab' => 'visits']) }}" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        @if(hasPermission('edit_appointment', $can))
                                        <button onclick="openEditVisitModal({{ $visit->visit_id }}, false)" class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] text-xs" title="edit">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        @endif
                                        @if(hasPermission('delete_appointment', $can))
                                        <form action="{{ route('medical.visits.destroy', $visit->visit_id) }}" method="POST" onsubmit="return confirm('Delete this visit?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="active_tab" value="visits">
                                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs" title="delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-gray-500 py-4">No visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(isset($visits) && method_exists($visits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $visits->firstItem() }} to {{ $visits->lastItem() }} of
                    {{ $visits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    {{ $visits->appends(['active_tab' => 'visits'])->links() }}
                </div>
            </div>
            @endif
        </div>

        <!-- Add Visit Modal -->
        <div id="addVisitModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">Add Visit</h3>
                    <button onclick="closeAddVisitModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
                </div>
                <form id="addVisitForm" method="POST" action="{{ route('medical.visits.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="active_tab" value="visits">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Date</label>
                            <input type="date" name="visit_date" id="add_visit_date" value="{{ now()->format('Y-m-d') }}" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Owner</label>
                            <select id="add_owner_id" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="" selected disabled>Select owner</option>
                                @foreach(($filteredOwners ?? []) as $owner)
                                    <option value="{{ $owner->own_id }}">{{ $owner->own_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium">Pets for selected owner</label>
                            <div id="add_owner_pets_container" class="space-y-3 border border-gray-200 rounded p-3 max-h-64 overflow-y-auto">
                                <div class="text-gray-500 text-sm">Select an owner to load their pets.</div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Tick the pets to include and set their weight, temperature and service type.</p>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium">Patient Type</label>
                            <select name="patient_type" id="add_patient_type" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                                <option value="Outpatient">Outpatient</option>
                                <option value="Inpatient">Inpatient</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">All new visits will be created with status <strong>Arrived</strong>.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeAddVisitModal()" class="px-4 py-2 border rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Visit Modal -->
        <div id="editVisitModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold" id="editVisitTitle">Edit Visit</h3>
                    <button onclick="closeEditVisitModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
                </div>
                <form id="editVisitForm" method="POST" action="#" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" value="visits">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Date</label>
                            <input type="date" name="visit_date" id="edit_visit_date" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Pet</label>
                            <select name="pet_id" id="edit_pet_id" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                                @foreach(($filteredPets ?? []) as $pet)
                                    <option value="{{ $pet->pet_id }}">{{ $pet->pet_name }} ({{ $pet->owner->own_name ?? 'N/A' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Weight (kg)</label>
                            <input type="number" step="0.01" name="weight" id="edit_weight" class="border border-gray-300 rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature" id="edit_temperature" class="border border-gray-300 rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Patient Type</label>
                            <select name="patient_type" id="edit_patient_type" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                                @foreach(['Outpatient','Inpatient','Emergency'] as $pt)
                                    <option value="{{ $pt }}">{{ $pt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <select name="visit_status" id="edit_visit_status" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="arrived">Arrived</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeEditVisitModal()" class="px-4 py-2 border rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded">Update</button>
                    </div>
                </form>
            </div>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Data for Visits modal rendering -->
        <script type="application/json" id="visit_pets_data">
            {!! collect($filteredPets ?? [])->map(function($p){
                return [
                    'pet_id' => $p->pet_id,
                    'pet_name' => $p->pet_name,
                    'owner_id' => $p->owner->own_id ?? null,
                    'owner_name' => $p->owner->own_name ?? null,
                ];
            })->values()->toJson() !!}
        </script>
        <script type="application/json" id="visit_service_types">
            {!! collect($serviceTypes ?? [])->values()->toJson() !!}
        </script>

<style>
/* Referral-specific styles */
.referral-container {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    background-color: white;
}

.referral-container .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 20px;
}

.referral-container .clinic-logo {
    background-color: #ff8c42;
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    font-size: 18px;
}

.referral-container .form-title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.referral-container .form-section {
    margin-bottom: 25px;
}

.referral-container .form-row {
    display: flex;
    margin-bottom: 15px;
    align-items: center;
}

.referral-container .form-label {
    font-weight: bold;
    min-width: 120px;
    color: #555;
}

.referral-container .form-value {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.referral-container .section-title {
    font-weight: bold;
    font-size: 16px;
    color: #333;
    margin-bottom: 15px;
    padding-bottom: 5px;
}

.referral-container .test-note, 
.referral-container .med-note {
    text-align: center;
    font-weight: bold;
    color: #666;
    margin: 15px 0;
    font-style: italic;
}

.referral-container .referral-info {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #ff8c42;
}

.referral-container .vet-name {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
}

.referral-container .clinic-details {
    color: #666;
    margin-bottom: 5px;
}

/* Print styles for referral - consolidated below */
</style>

<style>
/* Tab Styles */
/* Tab styles - matching pet management */
.tab-button {
    border-bottom-color: transparent;
    color: #6B7280;
}

.tab-button.active {
    border-bottom-color: #0f7ea0;
    color: #0f7ea0;
}

.tab-content {
    display: block;
}

.tab-content.hidden {
    display: none;
}

/* Prescription Styles */
.prescription-container {
    font-family: Arial, sans-serif;
    max-width: 700px;
    margin: 0 auto;
    border: 1px solid #000;
    background-color: white;
}

.medication-item {
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 12px;
    padding: 8px;
    border-left: 3px solid #dc2626;
    background-color: #fef2f2;
}

.medication-field {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    background-color: #f9fafb;
}

.product-suggestions {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
}

.product-suggestion-item:hover {
    background-color: #f3f4f6;
}

.product-suggestion-item:last-child {
    border-bottom: none;
}

.rx-symbol {
    text-align: left !important;
    margin: 20px 0 !important;
}

/* Print Styles */
@media print {
    @page {
        margin: 0.3in;
        size: A4;
    }
    
    body * {
        visibility: hidden;
    }
    
    /* Only show the active print container */
    .print-prescription,
    .print-prescription *,
    .print-referral,
    .print-referral * {
        visibility: visible !important;
    }
    
    .print-prescription {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: block !important;
        background: white !important;
    }
    
    .print-prescription .prescription-container {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: 2px solid #000 !important;
        padding: 30px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        page-break-inside: avoid;
    }
    
    .print-referral {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: block !important;
        background: white !important;
    }
    
    .print-referral .referral-container {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: 1px solid #000 !important;
        padding: 15px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        page-break-inside: auto;
    }
    
    /* Referral specific print optimizations */
    .print-referral .header {
        margin-bottom: 15px !important;
    }
    
    .print-referral .header img {
        width: 100% !important;
        height: auto !important;
        max-height: 100px !important;
        min-height: 80px !important;
        object-fit: contain !important;
        object-position: center !important;
    }
    
    .print-referral .header div[style*="background-color: #f88e28"] {
        background-color: #f88e28 !important;
        padding: 16px !important;
        border-radius: 8px !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    .print-referral .header {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .print-referral .patient-info {
        margin-bottom: 12px !important;
    }
    
    .print-referral .form-section {
        margin-bottom: 10px !important;
        page-break-inside: avoid;
    }
    
    .print-referral .section-title {
        font-size: 14px !important;
        margin-bottom: 6px !important;
        padding-bottom: 3px !important;
    }
    
    .print-referral .text-sm {
        font-size: 12px !important;
        line-height: 1.3 !important;
    }
    
    .print-referral .text-xs {
        font-size: 11px !important;
        line-height: 1.3 !important;
    }
    
    .print-referral .grid {
        gap: 6px !important;
    }
    
    .print-referral .referral-info {
        padding: 10px !important;
        margin-bottom: 8px !important;
    }
    
    .print-referral .mb-1 {
        margin-bottom: 3px !important;
    }
    
    .print-referral .mb-2 {
        margin-bottom: 6px !important;
    }
    
    .print-referral .mb-3 {
        margin-bottom: 8px !important;
    }
    
    .print-referral .mb-4 {
        margin-bottom: 10px !important;
    }
    
    .no-print {
        display: none !important;
        visibility: hidden !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .clinic-name {
        color: #a86520 !important;
    }
    
    .medication-item {
        border-left: 3px solid #dc2626 !important;
        background-color: #fef2f2 !important;
    }
}
</style>

<script>
// Global variables
let currentForm = null;
let selectedServices = [];
let medicationCounter = 0;
let currentPrescriptionId = null;
let currentReferralId = null;
let availableVaccineProducts = [];

// Setup CSRF token
function setupCSRF() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.csrfToken = token.getAttribute('content');
    }
}

// Tab functionality
// Tab switching functionality - matching pet management
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Add active class to selected tab button
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Update URL parameter without page reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

// ===== Visits Modals Helpers =====
function openAddVisitModal() {
    showTab('visits');
    const modal = document.getElementById('addVisitModal');
    if (!modal) return;
    // Reset form
    const form = document.getElementById('addVisitForm');
    if (form) form.reset();
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('add_visit_date');
    if (dateInput) dateInput.value = today;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAddVisitModal() {
    const modal = document.getElementById('addVisitModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function filterPetsByOwner(ownerId) {
    const petsSelect = document.getElementById('add_pet_ids');
    if (!petsSelect) return;
    const options = petsSelect.querySelectorAll('option');
    options.forEach(opt => {
        const belongs = String(opt.dataset.owner || '') === String(ownerId || '');
        // Show only matching owner's pets
        opt.hidden = !belongs;
        if (!belongs) opt.selected = false;
    });
}

function openEditVisitModal(visitId, attending) {
    showTab('visits');
    fetch(`/medical-management/visits/${visitId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(v => {
        const modal = document.getElementById('editVisitModal');
        const form = document.getElementById('editVisitForm');
        if (!modal || !form) return;
        form.action = `/medical-management/visits/${visitId}`;
        const title = document.getElementById('editVisitTitle');
        if (title) title.textContent = attending ? 'Attend Visit' : 'Edit Visit';

        const dateInput = document.getElementById('edit_visit_date');
        const petSelect = document.getElementById('edit_pet_id');
        const weightInput = document.getElementById('edit_weight');
        const tempInput = document.getElementById('edit_temperature');
        const typeSelect = document.getElementById('edit_patient_type');
        const statusSelect = document.getElementById('edit_visit_status');

        if (dateInput) dateInput.value = v.visit_date?.substring(0,10) || '';
        if (petSelect) petSelect.value = v.pet_id;
        if (weightInput) weightInput.value = v.weight ?? '';
        if (tempInput) tempInput.value = v.temperature ?? '';
        if (typeSelect) typeSelect.value = v.patient_type ?? 'Outpatient';
        if (statusSelect && v.visit_status) {
            statusSelect.value = v.visit_status;
        } else if (statusSelect && attending) {
            statusSelect.value = 'arrived';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    })
    .catch(() => alert('Failed to load visit details.'));
}

function closeEditVisitModal() {
    const modal = document.getElementById('editVisitModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('DOMContentLoaded', function() {
    // Default to Visits tab on load
    try { showTab('visits'); } catch(e) {}
    const ownerSelect = document.getElementById('add_owner_id');
    // Build pets dataset for rendering owner pets
    const allPets = (function(){
        try {
            return JSON.parse(document.getElementById('visit_pets_data').textContent);
        } catch { return []; }
    })();
    const serviceTypes = (function(){
        try {
            return JSON.parse(document.getElementById('visit_service_types').textContent);
        } catch { return []; }
    })();

    function renderOwnerPets(ownerId) {
        const container = document.getElementById('add_owner_pets_container');
        if (!container) return;
        const pets = allPets.filter(p => String(p.owner_id) === String(ownerId));
        if (pets.length === 0) {
            container.innerHTML = '<div class="text-gray-500 text-sm">No pets found for the selected owner.</div>';
            return;
        }
        container.innerHTML = pets.map(p => {
            // Fixed serv_type options
            const fixedTypes = ['boarding','check up','deworming','diagnostics','emergency','grooming','surgical','vaccination'];
            const serviceCheckboxes = fixedTypes.map(type => `
                <label class='inline-flex items-center mr-3 mb-1'>
                    <input type="checkbox" name="service_type[${p.pet_id}][]" value="${type}" class="service-checkbox mr-1"> ${type}
                </label>
            `).join('');
            return `
            <div class="border border-gray-200 rounded p-3">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="pet_ids[]" value="${p.pet_id}" class="mt-1 pet-check" data-pet="${p.pet_id}">
                    <div class="flex-1">
                        <div class="font-medium">${p.pet_name}</div>
                        <div class="grid grid-cols-3 gap-3 mt-2 text-sm">
                            <div>
                                <label class="block text-xs text-gray-600">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight[${p.pet_id}]" class="border border-gray-300 rounded px-2 py-1 w-full" placeholder="e.g. 3.50">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">Temperature (°C)</label>
                                <input type="number" step="0.1" name="temperature[${p.pet_id}]" class="border border-gray-300 rounded px-2 py-1 w-full" placeholder="e.g. 38.5">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">Service Type(s)</label>
                                <div class="flex flex-wrap" data-service-group="${p.pet_id}">${serviceCheckboxes}</div>
                                <p class="text-xs text-gray-400 mt-1">Select one or more services for this pet's visit.</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>`;
        }).join('');

        // Auto-check the pet when any service type is selected
        const groups = container.querySelectorAll('[data-service-group]');
        groups.forEach(g => {
            const pet = g.getAttribute('data-service-group');
            const petBox = container.querySelector(`input.pet-check[data-pet="${pet}"]`);
            g.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', () => {
                    if (cb.checked && petBox && !petBox.checked) petBox.checked = true;
                });
            });
        });
    }

    if (ownerSelect) {
        ownerSelect.addEventListener('change', function() {
            renderOwnerPets(this.value);
        });
    }
});

function openVaccineDetailsModal(appointId, petName, appointDate, serviceId, prodId, nextDose, batchNo, notes) {
    const modal = document.getElementById('vaccineDetailsModal');
    const form = document.getElementById('vaccineDetailsForm');
    
    // 1. Reset Form and set action URL
    form.reset();
    form.action = `/medical-management/appointments/${appointId}/record-vaccine-details`;
    
    // 2. Set hidden IDs and display info
    document.getElementById('vacc_appoint_id').value = appointId;
    document.getElementById('vacc_service_id').value = serviceId;
    document.getElementById('vacc_pet_name').textContent = petName;
    document.getElementById('vacc_appoint_date').textContent = formatDate(appointDate); // Assuming formatDate exists
    
    // 3. Populate fields with existing data
    document.getElementById('vacc_prod_id').value = prodId;
    document.getElementById('vacc_next_dose_input').value = nextDose;
    document.getElementById('vacc_batch_no_input').value = batchNo;
    document.getElementById('vacc_notes_input').value = notes;

    // --- FIX START ---
    // Clear previous search results and ensure the search bar is reset
    const searchInput = document.getElementById('vacc_product_search');
    const suggestionsDiv = document.getElementById('vacc_product_suggestions');
    searchInput.value = '';
    suggestionsDiv.innerHTML = '';
    suggestionsDiv.classList.add('hidden');
    // --- FIX END ---

    // 4. Load Products linked to the Vaccination service (AJAX call to a new endpoint)
    fetch(`/medical-management/services/${serviceId}/products`, {
        headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.products) {
            availableVaccineProducts = data.products;
            
            // Set search input display for existing vaccine
            const existingProduct = availableVaccineProducts.find(p => String(p.prod_id) === String(prodId));
            
            // Populate the search bar and title after product list is loaded
            searchInput.value = existingProduct ? existingProduct.product_name : '';
            document.getElementById('vaccineDetailsModalTitle').textContent = prodId ? 'Edit Vaccine Record' : 'Record Vaccine Details';

        } else {
            availableVaccineProducts = [];
            alert('Error fetching products linked to the Vaccination service.');
        }
    })
    .catch(error => {
        console.error('Error fetching service products:', error);
        alert('Failed to load available products.');
    });

    // 5. Show Modal and ensure search listener is attached
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Since the listener logic itself is separate and self-contained, 
    // we only need to call it once globally (see section 2)
    // Here we ensure the listener is ready to work:
    setupVaccineProductSearchListener('vacc_product_search', 'vacc_prod_id', 'vacc_product_suggestions');
}

function closeVaccineDetailsModal() {
    document.getElementById('vaccineDetailsModal').classList.remove('flex');
    document.getElementById('vaccineDetailsModal').classList.add('hidden');
}

function setupVaccineProductSearchListener(searchInputId, productIdInputId, suggestionsDivId) {
    const searchInput = document.getElementById(searchInputId);
    const productIdInput = document.getElementById(productIdInputId);
    const suggestionsDiv = document.getElementById(suggestionsDivId);
    
    // Add null checks for safety (in case the DOM hasn't fully loaded the elements yet)
    if (!searchInput || !productIdInput || !suggestionsDiv) return;

    let searchTimeout;

    // Attach the input event listener once.
    // Use .setAttribute('oninput', ...) if you suspect the listener isn't clearing between modal uses.
    searchInput.oninput = function() { 
        // --- Listener Logic ---
        const query = this.value.toLowerCase().trim();
        clearTimeout(searchTimeout);

        // Clear product ID if user manually types (to force re-selection)
        productIdInput.value = '';

        if (query.length < 2) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            const filtered = availableVaccineProducts.filter(p => p.product_name.toLowerCase().includes(query));
            suggestionsDiv.innerHTML = '';
            
            if (filtered.length > 0) {
                filtered.forEach(product => {
                    const item = document.createElement('div');
                    item.className = 'product-suggestion-item text-sm px-3 py-2 cursor-pointer hover:bg-gray-100 border-b last:border-b-0';
                    item.innerHTML = `<div>${product.product_name}</div><div class="text-xs text-gray-500">Stock: ${product.current_stock}</div>`;
                   item.onclick = function() {
                        // **CRITICAL FIX: Ensure both fields are set and the suggestions close**
                        document.getElementById(productIdInputId).value = product.prod_id; // Set the hidden ID
                        document.getElementById(searchInputId).value = product.product_name; // Set the visible text
                        suggestionsDiv.classList.add('hidden');
                    };
                    suggestionsDiv.appendChild(item);
                });
                suggestionsDiv.classList.remove('hidden');
            } else {
                suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-gray-500 px-3 py-2">No linked products found matching query.</div>';
                suggestionsDiv.classList.remove('hidden');
            }
        }, 300);
    };
    
    // Global click handler to dismiss suggestions when clicking outside the search area
    document.addEventListener('click', function(e) {
        // Use .contains() to check if the click target is outside the search input and suggestions list
        if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}


// ==================== APPOINTMENT FUNCTIONS ====================

function openAddModal() {
    // Reset the form
    document.getElementById('addForm').reset();
    
    // Clear any existing service selections
    selectedServices = [];
    document.getElementById('selectedServicesDisplay').value = '';
    
    // Remove any existing hidden service inputs
    const existingInputs = document.querySelectorAll('#addForm input[name="services[]"]');
    existingInputs.forEach(n => n.remove());
    
    // Show the modal
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addModal').classList.add('flex');
}

function closeAddModal() {
    document.getElementById('addModal').classList.remove('flex');
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(appointment) {
    document.getElementById('editForm').action = `/medical-management/appointments/${appointment.appoint_id}`;
    document.getElementById('edit_appoint_id').value = appointment.appoint_id ?? '';
    document.getElementById('edit_appoint_date').value = appointment.appoint_date ?? '';

    const timeValue = formatTime24hr(appointment.appoint_time ?? '');
    document.getElementById('edit_appoint_time').value = timeValue;
    //document.getElementById('edit_appoint_time').value = appointment.appoint_time ?? '';
    document.getElementById('edit_appoint_contactNum').value = appointment.appoint_contactNum ?? '';
    document.getElementById('edit_appoint_status').value = appointment.appoint_status ?? '';
    document.getElementById('edit_appoint_type').value = appointment.appoint_type ?? '';
    document.getElementById('edit_appoint_description').value = appointment.appoint_description ?? '';

    document.getElementById('edit_owner_id').value = appointment.pet?.owner?.own_id ?? '';
    populateOwnerDetailsEdit(document.getElementById('edit_owner_id'), appointment.pet_id ?? null);

    const serviceIds = (appointment.services || []).map(s => String(s.serv_id));
    createHiddenServiceInputs('editForm', serviceIds);
    const names = (appointment.services || []).map(s => s.serv_name).join(', ');
    document.getElementById('edit_selectedServicesDisplay').value = names || 'No services selected';
    

    
    // Store selected services globally
    selectedServices = serviceIds;

    const m = document.getElementById('editModal'); 
    m.classList.remove('hidden'); 
    m.classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('flex');
    document.getElementById('editModal').classList.add('hidden');
}

function formatTime24hr(timeString) {
    if (!timeString) return '';
    try {
        // This ensures the string is trimmed to HH:MM format (e.g., '14:00:00' -> '14:00')
        if (timeString.length >= 5 && timeString.includes(':')) {
             return timeString.substring(0, 5);
        }
    } catch (e) {
        console.error('Error formatting time:', e);
    }
    return timeString;
}

// ==================== SERVICE SELECTION FUNCTIONS ====================

// Helper function to get selected services from form
function getSelectedFromForm(formId) {
    return Array.from(document.querySelectorAll(`#${formId} input[name="services[]"]`)).map(i => String(i.value));
}

// Helper function to create hidden service inputs
function createHiddenServiceInputs(formId, ids) {
    const existingInputs = document.querySelectorAll(`#${formId} input[name="services[]"]`);
    existingInputs.forEach(n => n.remove());
    
    const form = document.getElementById(formId);
    if (!form) return;
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'services[]';
        input.value = id;
        form.appendChild(input);
    });
}

// Toggle service selection visibility
function toggleServiceSelection(formType) {
    const selectionDiv = document.getElementById(`${formType}ServiceSelection`);
    const buttonText = document.getElementById(`${formType}ServiceButtonText`);
    
    if (selectionDiv.classList.contains('hidden')) {
        // Show selection
        selectionDiv.classList.remove('hidden');
        buttonText.textContent = 'Hide Services';
        
        // Reset search
        const searchInput = document.getElementById(`${formType}ServiceSearch`);
        if (searchInput) {
            searchInput.value = '';
            filterServices(formType);
        }
        
        // Load currently selected services
        const formId = formType === 'add' ? 'addForm' : 'editForm';
        const selectedServices = getSelectedFromForm(formId);
        
        // Update checkboxes
        const checkboxes = document.querySelectorAll(`.${formType}-service-checkbox`);
        checkboxes.forEach(cb => {
            cb.checked = selectedServices.includes(String(cb.value));
        });
        
        updateServiceCount(formType);
        
        // Focus on search input
        setTimeout(() => {
            if (searchInput) searchInput.focus();
        }, 100);
    } else {
        // Hide selection
        selectionDiv.classList.add('hidden');
        buttonText.textContent = 'Select Services';
    }
}

// Cancel service selection
function cancelServiceSelection(formType) {
    const selectionDiv = document.getElementById(`${formType}ServiceSelection`);
    const buttonText = document.getElementById(`${formType}ServiceButtonText`);
    const searchInput = document.getElementById(`${formType}ServiceSearch`);
    
    // Reset search
    if (searchInput) {
        searchInput.value = '';
        filterServices(formType);
    }
    
    selectionDiv.classList.add('hidden');
    buttonText.textContent = 'Select Services';
    
    // Restore previous selections
    const formId = formType === 'add' ? 'addForm' : 'editForm';
    const selectedServices = getSelectedFromForm(formId);
    
    const checkboxes = document.querySelectorAll(`.${formType}-service-checkbox`);
    checkboxes.forEach(cb => {
        cb.checked = selectedServices.includes(String(cb.value));
    });
    
    updateServiceCount(formType);
}

// Save selected services
function saveSelectedServices(formType) {
    const checkboxes = document.querySelectorAll(`.${formType}-service-checkbox:checked`);
    const selectedServices = Array.from(checkboxes).map(cb => String(cb.value));
    const names = Array.from(checkboxes).map(cb => cb.dataset.name);
    
    console.log('Saving services:', selectedServices);
    console.log('Service names:', names);
    
    const formId = formType === 'add' ? 'addForm' : 'editForm';
    
    // Remove old hidden inputs and create new ones
    createHiddenServiceInputs(formId, selectedServices);
    
    // Update the display field
    const displayField = document.getElementById(
        formType === 'add' ? 'selectedServicesDisplay' : 'edit_selectedServicesDisplay'
    );
    
    if (displayField) {
        displayField.value = names.length > 0 ? names.join(', ') : 'No services selected';
        
        // Add visual feedback
        displayField.style.backgroundColor = '#d1fae5';
        displayField.style.transition = 'background-color 0.3s ease';
        
        setTimeout(() => {
            displayField.style.backgroundColor = '';
        }, 800);
    }
    
    // Reset search
    const searchInput = document.getElementById(`${formType}ServiceSearch`);
    if (searchInput) {
        searchInput.value = '';
        filterServices(formType);
    }
    
    // Hide the selection area
    const selectionDiv = document.getElementById(`${formType}ServiceSelection`);
    const buttonText = document.getElementById(`${formType}ServiceButtonText`);
    
    selectionDiv.classList.add('hidden');
    buttonText.textContent = 'Select Services';
    
    console.log('Services saved. Hidden inputs created:', 
        document.querySelectorAll(`#${formId} input[name="services[]"]`).length);
}

// Update service count
function updateServiceCount(formType) {
    const checkedCount = document.querySelectorAll(`.${formType}-service-checkbox:checked`).length;
    const countElement = document.getElementById(`${formType}SelectedCount`);
    if (countElement) {
        countElement.textContent = `${checkedCount} selected`;
    }
}

// Filter services based on search input
function filterServices(formType) {
    const searchInput = document.getElementById(`${formType}ServiceSearch`);
    const searchTerm = searchInput.value.toLowerCase().trim();
    const serviceItems = document.querySelectorAll(`#${formType}ServiceGrid .service-item`);
    const noResults = document.getElementById(`${formType}NoResults`);
    const serviceGrid = document.getElementById(`${formType}ServiceGrid`);
    const clearButton = document.getElementById(`${formType}ClearSearch`);
    const searchStats = document.getElementById(`${formType}SearchStats`);
    const foundCount = document.getElementById(`${formType}FoundCount`);
    
    let visibleCount = 0;
    
    // Show/hide clear button
    if (searchTerm.length > 0) {
        clearButton.classList.remove('hidden');
    } else {
        clearButton.classList.add('hidden');
    }
    
    // Filter service items
    serviceItems.forEach(item => {
        const serviceName = item.getAttribute('data-service-name');
        
        if (serviceName.includes(searchTerm)) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });
    
    // Show/hide no results message
    if (visibleCount === 0 && searchTerm.length > 0) {
        noResults.classList.remove('hidden');
        serviceGrid.classList.add('hidden');
        searchStats.classList.add('hidden');
    } else {
        noResults.classList.add('hidden');
        serviceGrid.classList.remove('hidden');
        
        // Show search statistics
        if (searchTerm.length > 0) {
            searchStats.classList.remove('hidden');
            foundCount.textContent = visibleCount;
        } else {
            searchStats.classList.add('hidden');
        }
    }
}

// Clear service search
function clearServiceSearch(formType) {
    const searchInput = document.getElementById(`${formType}ServiceSearch`);
    searchInput.value = '';
    filterServices(formType);
    searchInput.focus();
}

// Select all visible services
function selectAllServices(formType) {
    const checkboxes = document.querySelectorAll(`.${formType}-service-checkbox`);
    const serviceItems = document.querySelectorAll(`#${formType}ServiceGrid .service-item`);
    
    checkboxes.forEach((checkbox, index) => {
        const serviceItem = serviceItems[index];
        // Only check visible items
        if (!serviceItem.classList.contains('hidden')) {
            checkbox.checked = true;
        }
    });
    
    updateServiceCount(formType);
}

function populateOwnerDetails(select) {
    const petSelect = document.getElementById('pet_id');
    petSelect.innerHTML = '<option disabled selected>Select Pet</option>';
    const pets = JSON.parse(select.selectedOptions[0].dataset.pets || '[]');
    pets.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        petSelect.appendChild(opt);
    });
    document.getElementById('appoint_contactNum').value = select.selectedOptions[0].dataset.contact || '';
}

function populateOwnerDetailsEdit(select, petId = null) {
    const petSelect = document.getElementById('edit_pet_id');
    petSelect.innerHTML = '<option disabled selected>Select Pet</option>';
    const pets = JSON.parse(select.selectedOptions[0].dataset.pets || '[]');
    pets.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        if (String(p.id) === String(petId)) opt.selected = true;
        petSelect.appendChild(opt);
    });
    document.getElementById('edit_appoint_contactNum').value = select.selectedOptions[0].dataset.contact || '';
}
// ==================== PRESCRIPTION FUNCTIONS ====================

function openPrescriptionModal() {
    const form = document.getElementById('prescriptionForm');
    form.reset();
    form.action = "{{ route('medical.prescriptions.store') }}";
    document.getElementById('prescriptionFormMethod').value = 'POST';
    document.getElementById('prescriptionModalTitle').textContent = 'Add Prescription';
    document.getElementById('prescription_id').value = '';
    document.getElementById('medicationContainer').innerHTML = '';
    
    medicationCounter = 0;
    addMedicationField();

    document.getElementById('differential_diagnosis').value = ''; 
    
    document.getElementById('prescriptionModal').classList.remove('hidden');
}

function openPrescriptionFromAppointment(appointmentId, petName = null, appointDate = null) {
    // First, open the modal and reset form
    const form = document.getElementById('prescriptionForm');
    form.reset();
    form.action = "{{ route('medical.prescriptions.store') }}";
    document.getElementById('prescriptionFormMethod').value = 'POST';
    document.getElementById('prescriptionModalTitle').textContent = 'Add Prescription';
    document.getElementById('prescription_id').value = '';
    document.getElementById('medicationContainer').innerHTML = '';
    
    medicationCounter = 0;
    addMedicationField();
    document.getElementById('differential_diagnosis').value = ''; 
    
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('prescription_date').value = today;
    
    // Show the modal
    document.getElementById('prescriptionModal').classList.remove('hidden');
    
    // Then fetch and populate appointment data if appointmentId is provided
    if (appointmentId) {
        fetch(`/medical-management/appointments/${appointmentId}/for-prescription`, {
            headers: {
                'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received prescription data:', data);
            
            // Auto-populate the pet
            if (data.pet_id) {
                document.getElementById('prescription_pet_id').value = data.pet_id;
            }
            
            // Auto-populate the date from appointment
            if (data.appointment_date) {
                document.getElementById('prescription_date').value = data.appointment_date;
            }
            
            // Optional: Add appointment info to notes
            if (data.services) {
                const notesField = document.getElementById('prescription_notes');
                notesField.value = `Prescription for appointment on ${data.appointment_date}\nServices: ${data.services}`;
            }
        })
        .catch(error => {
            console.error('Error loading appointment data:', error);
            // If fetch fails but we have the data from parameters, use that
            if (petName && appointDate) {
                // Find the pet in the dropdown by name
                const petSelect = document.getElementById('prescription_pet_id');
                const options = petSelect.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].text.includes(petName)) {
                        petSelect.value = options[i].value;
                        break;
                    }
                }
                document.getElementById('prescription_date').value = appointDate;
            }
        });
    }
}

function closePrescriptionModal() {
    document.getElementById('prescriptionModal').classList.add('hidden');
}

function addMedicationField() {
    const container = document.getElementById('medicationContainer');
    const fieldId = ++medicationCounter;
    
    const fieldHtml = `
        <div class="medication-field" data-field-id="${fieldId}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-sm font-medium text-gray-700">Medication ${fieldId}</h4>
                ${fieldId > 1 ? `<button type="button" onclick="removeMedicationField(${fieldId})" class="text-red-500 hover:text-red-700 text-sm"><i class="fas fa-trash"></i> Remove</button>` : ''}
            </div>
            
            <div class="grid grid-cols-1 gap-3 mb-3">
                <div class="relative">
                    <label class="block text-xs text-gray-600 mb-1">Search Product or Enter Manually</label>
                    <input type="text" 
                           class="product-search w-full border px-2 py-2 rounded text-sm" 
                           placeholder="Type product name or search from database..."
                           data-field-id="${fieldId}">
                    <div class="product-suggestions hidden" data-field-id="${fieldId}"></div>
                    <input type="hidden" class="selected-product-id" data-field-id="${fieldId}">
                    <input type="hidden" class="selected-product-name" data-field-id="${fieldId}">
                </div>
                
                <div class="bg-gray-50 p-2 rounded text-xs">
                    <div class="selected-product-display" data-field-id="${fieldId}">
                        Manual entry or select from search results above
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs text-gray-600 mb-1">Instructions (Sig.) - Use semicolon (;) to separate multiple instructions</label>
                <textarea class="medication-instructions w-full border px-2 py-2 rounded text-sm" 
                          rows="2" 
                          data-field-id="${fieldId}"
                          placeholder="e.g., Use it everyday; Apply twice daily; Take with food" required></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', fieldHtml);
    setupProductSearch(fieldId);
}

function removeMedicationField(fieldId) {
    const field = document.querySelector(`[data-field-id="${fieldId}"]`);
    if (field && document.querySelectorAll('.medication-field').length > 1) {
        field.remove();
    }
}

function setupProductSearch(fieldId) {
    const searchInput = document.querySelector(`input[data-field-id="${fieldId}"].product-search`);
    const suggestionsDiv = document.querySelector(`.product-suggestions[data-field-id="${fieldId}"]`);
    const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`);
    const productNameInput = document.querySelector(`.selected-product-name[data-field-id="${fieldId}"]`);
    const displayDiv = document.querySelector(`.selected-product-display[data-field-id="${fieldId}"]`);
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        productNameInput.value = query;
        if (query) {
            displayDiv.innerHTML = `<span class="text-blue-700 font-medium">Manual Entry: ${query}</span>`;
            displayDiv.classList.remove('bg-gray-100');
            displayDiv.classList.add('bg-blue-100');
        } else {
            displayDiv.innerHTML = 'Manual entry or select from search results above';
            displayDiv.classList.remove('bg-blue-100');
            displayDiv.classList.add('bg-gray-100');
        }
        
        if (query.length < 2) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-gray-500">Searching...</div>';
            suggestionsDiv.classList.remove('hidden');
            
            fetch(`{{ route('medical.prescriptions.search-products') }}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(products => {
                    if (products.length > 0) {
                        suggestionsDiv.innerHTML = products.map(product => `
                            <div class="product-suggestion-item" data-product-id="${product.id}" data-product-name="${product.name}">
                                <div class="font-medium">${product.name}</div>
                                <div class="text-xs text-gray-500">₱${parseFloat(product.price || 0).toFixed(2)} - ${product.type || 'Product'}</div>
                            </div>
                        `).join('');
                        
                        suggestionsDiv.classList.remove('hidden');
                        
                        suggestionsDiv.querySelectorAll('.product-suggestion-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const productId = this.dataset.productId;
                                const productName = this.dataset.productName;
                                
                                productIdInput.value = productId;
                                productNameInput.value = productName;
                                displayDiv.innerHTML = `<span class="text-green-700 font-medium">Selected: ${productName}</span>`;
                                displayDiv.classList.remove('bg-gray-100', 'bg-blue-100');
                                displayDiv.classList.add('bg-green-100');
                                
                                searchInput.value = productName;
                                suggestionsDiv.classList.add('hidden');
                            });
                        });
                    } else {
                        suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-gray-500">No products found in database. You can still type manually above.</div>';
                        suggestionsDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error searching products:', error);
                    suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-orange-500">Search temporarily unavailable. You can still type manually above.</div>';
                    suggestionsDiv.classList.remove('hidden');
                });
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (!searchInput.parentElement.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}

// Form submission handler for prescriptions
document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const medications = [];
    document.querySelectorAll('.medication-field').forEach(field => {
        const fieldId = field.dataset.fieldId;
        const productId = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`).value;
        const productName = document.querySelector(`.selected-product-name[data-field-id="${fieldId}"]`).value || 
                           document.querySelector(`input[data-field-id="${fieldId}"].product-search`).value;
        const instructions = document.querySelector(`.medication-instructions[data-field-id="${fieldId}"]`).value;
        
        if (productName && instructions) {
            medications.push({
                product_id: productId || null,
                product_name: productName,
                instructions: instructions
            });
        }
    });
    
    if (medications.length === 0) {
        alert('Please add at least one medication with instructions');
        return;
    }
    
    const petId = document.getElementById('prescription_pet_id').value;
    const prescriptionDate = document.getElementById('prescription_date').value;
    
    if (!petId) {
        alert('Please select a pet');
        return;
    }
    
    if (!prescriptionDate) {
        alert('Please select a date');
        return;
    }
    
    const existingHiddenInput = this.querySelector('input[name="medications_json"]');
    if (existingHiddenInput) {
        existingHiddenInput.remove();
    }
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'medications_json';
    hiddenInput.value = JSON.stringify(medications);
    this.appendChild(hiddenInput);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    this.submit();
});

function editPrescription(id) {
    fetch(`/medical-management/prescriptions/${id}/edit`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById('prescriptionForm').reset();
            document.getElementById('prescription_id').value = id;
            document.getElementById('prescription_pet_id').value = data.pet_id;
            document.getElementById('prescription_date').value = data.prescription_date;
            document.getElementById('prescription_notes').value = data.notes || '';

            document.getElementById('medicationContainer').innerHTML = '';
            medicationCounter = 0;

            if (data.medications && data.medications.length > 0) {
                data.medications.forEach(med => {
                    addMedicationField();
                    const currentFieldId = medicationCounter;
                    
                    const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${currentFieldId}"]`);
                    const productNameInput = document.querySelector(`.selected-product-name[data-field-id="${currentFieldId}"]`);
                    const searchInput = document.querySelector(`input[data-field-id="${currentFieldId}"].product-search`);
                    const displayDiv = document.querySelector(`.selected-product-display[data-field-id="${currentFieldId}"]`);
                    const instructionsTextarea = document.querySelector(`.medication-instructions[data-field-id="${currentFieldId}"]`);
                    
                    productIdInput.value = med.product_id || '';
                    productNameInput.value = med.product_name || '';
                    searchInput.value = med.product_name || '';
                    displayDiv.innerHTML = `<span class="text-green-700 font-medium">Selected: ${med.product_name || 'Unknown Product'}</span>`;
                    displayDiv.classList.remove('bg-gray-100');
                    displayDiv.classList.add('bg-green-100');
                    instructionsTextarea.value = med.instructions || '';
                });
            } else {
                addMedicationField();
            }

            document.getElementById('prescriptionForm').action = `/medical-management/prescriptions/${id}`;
            document.getElementById('prescriptionFormMethod').value = 'PUT';
            document.getElementById('prescriptionModalTitle').textContent = 'Edit Prescription';
            document.getElementById('prescriptionModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error fetching prescription data:', error);
            alert('Error loading prescription data: ' + error.message);
        });
}
function populatePrescriptionData(button) {
    let medications = [];
    try {
        medications = JSON.parse(button.dataset.medication || '[]');
    } catch (e) {
        if (button.dataset.medication) {
            const oldMeds = button.dataset.medication.split(';');
            medications = oldMeds.map(med => ({
                product_name: med.trim(),
                instructions: '[Instructions will be added here]'
            }));
        }
    }
    
    const prescriptionData = {
        id: button.dataset.id,
        pet: button.dataset.pet,
        weight: button.dataset.weight || 'N/A',
        temp: button.dataset.temp || 'N/A',
        age: button.dataset.age || 'N/A',
        gender: button.dataset.gender || 'N/A',
        date: button.dataset.date,
        medications: medications,
        differentialDiagnosis: button.dataset.differentialDiagnosis || 'Not specified', // Fixed
        notes: button.dataset.notes || 'No specific recommendations',
        branchName: button.dataset.branchName.toUpperCase(),
        branchAddress: 'Address: ' + button.dataset.branchAddress,
        branchContact: "Contact No: " + button.dataset.branchContact
    };
    
    return prescriptionData;
}

function updatePrescriptionContent(targetId, data) {
    const container = document.getElementById(targetId);
    
    container.innerHTML = `
        <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
            <div class="flex-shrink-0">
                <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
            </div>
            <div class="flex-grow text-center">
                <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                    PETS 2GO VETERINARY CLINIC
                </div>
                <div class="branch-name text-lg font-bold underline text-center mt-1">
                    ${data.branchName}
                </div>
                <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                    <div>${data.branchAddress}</div>
                    <div>${data.branchContact}</div>
                </div>
            </div>
        </div>

        <div class="prescription-body">
            <div class="patient-info mb-6">
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div>
                        <div class="mb-2"><strong>DATE:</strong> ${data.date}</div>
                        <div class="mb-2"><strong>NAME OF PET:</strong> ${data.pet}</div>
                    </div>
                    <div class="text-center">
                        <div><strong>WEIGHT:</strong> ${data.weight}</div>
                        <div><strong>TEMP:</strong> ${data.temp}</div>
                    </div>
                    <div class="text-right">
                        <div><strong>AGE:</strong> ${data.age}</div>
                        <div><strong>GENDER:</strong> ${data.gender}</div>
                    </div>
                </div>
            </div>

            <div class="rx-symbol text-left my-8 text-6xl font-bold text-gray-800">℞</div>

            <div class="medication-section mb-8">
                <div class="section-title text-base font-bold mb-4">MEDICATION</div>
                <div class="space-y-3">
                    ${data.medications && data.medications.length > 0 ? data.medications.map((med, index) => `
                        <div class="medication-item">
                            <div class="text-sm font-medium text-red-600 mb-1">${index+1}. ${med.product_name || med.name || 'Unknown medication'}</div>
                            <div class="text-sm text-gray-700 ml-4"><strong>SIG.</strong> ${med.instructions || '[Instructions will be added here]'}</div>
                        </div>
                    `).join('') : '<div class="medication-item text-gray-500">No medications prescribed</div>'}
                </div>
            </div>

            <div class="differential-diagnosis mb-6">
                <h3 class="text-base font-bold mb-2">DIFFERENTIAL DIAGNOSIS:</h3>
                <div class="text-sm bg-blue-50 p-3 rounded border-l-4 border-blue-500">${data.differentialDiagnosis || 'Not specified'}</div>
            </div>

            <div class="recommendations mb-8">
                <h3 class="text-base font-bold mb-4">RECOMMENDATION/REMINDER:</h3>
                <div class="text-sm">${data.notes}</div>
            </div>

            <div class="footer text-right pt-8 border-t-2 border-black">
                <div class="doctor-info text-sm">
                    <div class="doctor-name font-bold mb-1">JAN JERICK M. GO DVM</div>
                    <div class="license-info text-gray-600">License No.: 0012045</div>
                    <div class="license-info text-gray-600">Attending Veterinarian</div>
                </div>
            </div>
        </div>
    `;
}
function viewPrescription(button) {
    console.log('All button data:', button.dataset); // Debug: see all data attributes
    
    currentPrescriptionId = button.dataset.id;
    
    // Get differential diagnosis - HTML data attributes with hyphens become camelCase in dataset
    let diffDiagnosis = button.dataset.differentialDiagnosis || 'Not specified';
    
    console.log('Differential Diagnosis:', diffDiagnosis); // Debug log
    
    // Set all the basic fields
    document.getElementById('viewPet').innerText = button.dataset.pet || 'N/A';
    document.getElementById('viewWeight').innerText = button.dataset.weight || 'N/A';
    document.getElementById('viewTemp').innerText = button.dataset.temp || 'N/A';
    document.getElementById('viewAge').innerText = button.dataset.age || 'N/A';
    document.getElementById('viewGender').innerText = button.dataset.gender || 'N/A';
    document.getElementById('viewDate').innerText = button.dataset.date || 'N/A';
    
    document.getElementById('branch_name').innerText = (button.dataset.branchName || 'Main Branch').toUpperCase();
    document.getElementById('branch_address').innerText = 'Address: ' + (button.dataset.branchAddress || 'Branch Address');
    document.getElementById('branch_contactNum').innerText = 'Contact No: ' + (button.dataset.branchContact || 'Contact Number');
    
    // Set differential diagnosis
    document.getElementById('viewDifferentialDiagnosis').innerText = diffDiagnosis;
    
    // Parse and display medications
    let medications = [];
    try {
        if (button.dataset.medication) {
            medications = JSON.parse(button.dataset.medication);
        }
    } catch (e) {
        console.error('Error parsing medications:', e);
    }
    
    const medsContainer = document.getElementById('medicationsList');
    medsContainer.innerHTML = '';
    
    if (medications && medications.length > 0) {
        medications.forEach((med, index) => {
            const medDiv = document.createElement('div');
            medDiv.classList.add('medication-item');
            medDiv.innerHTML = `
                <div class="text-sm font-medium text-red-600 mb-1">${index+1}. ${med.product_name || 'Unknown medication'}</div>
                <div class="text-sm text-gray-700 ml-4"><strong>SIG.</strong> ${med.instructions || 'No instructions'}</div>
            `;
            medsContainer.appendChild(medDiv);
        });
    } else {
        medsContainer.innerHTML = '<div class="medication-item text-gray-500">No medications prescribed</div>';
    }
    
    // Set notes
    document.getElementById('viewNotes').innerText = button.dataset.notes || 'No recommendations';
    
    // Show modal
    document.getElementById('viewPrescriptionModal').classList.remove('hidden');
}

function directPrint(button) {
    const data = populatePrescriptionData(button);
    updatePrescriptionContent('printContent', data);
    
    // Remove all print classes first
    document.getElementById('printContainer').classList.remove('print-prescription', 'print-referral');
    document.getElementById('printReferralContainer').classList.remove('print-prescription', 'print-referral');
    
    // Hide referral container
    document.getElementById('printReferralContainer').style.display = 'none';
    
    // Show prescription container with print class
    const printContainer = document.getElementById('printContainer');
    printContainer.style.display = 'block';
    printContainer.classList.add('print-prescription');
    
    setTimeout(() => {
        window.print();
        printContainer.style.display = 'none';
        printContainer.classList.remove('print-prescription');
    }, 200);
}

// ==================== REFERRAL FUNCTIONS ====================

function openReferralModal(appointmentId = null) {
    const form = document.getElementById('referralForm');
    form.reset();
    form.action = "{{ route('medical.referrals.store') }}";
    document.getElementById('referralFormMethod').value = 'POST';
    document.getElementById('referralModalTitle').textContent = 'Create Referral';
    document.getElementById('ref_id').value = '';
    
    // Set today's date as default
    document.getElementById('ref_date').value = new Date().toISOString().split('T')[0];
    
    // If appointment ID is provided, select it
    if (appointmentId) {
        document.getElementById('appointment_id').value = appointmentId;
        loadAppointmentDetails(appointmentId);
    } else {
        // Clear patient information
        clearPatientInfo();
    }
    
    document.getElementById('referralModal').classList.remove('hidden');
}

function closeReferralModal() {
    document.getElementById('referralModal').classList.add('hidden');
}

function loadAppointmentDetails(appointmentId) {
    if (!appointmentId) {
        clearPatientInfo();
        return;
    }
    
    fetch(`/medical-management/appointments/${appointmentId}/details`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.pet) {
            document.getElementById('ref_pet_name').textContent = data.pet.pet_name || '-';
            document.getElementById('ref_pet_gender').textContent = data.pet.pet_gender || '-';
            document.getElementById('ref_pet_dob').textContent = data.pet.pet_birthdate || '-';
            document.getElementById('ref_pet_species').textContent = data.pet.pet_species || '-';
            document.getElementById('ref_pet_breed').textContent = data.pet.pet_breed || '-';
            document.getElementById('ref_pet_weight').textContent = (data.pet.pet_weight || '-') + (data.pet.pet_weight ? ' kg' : '');
        }
        
        if (data.owner) {
            document.getElementById('ref_owner_name').textContent = data.owner.own_name || '-';
            document.getElementById('ref_owner_contact').textContent = data.owner.own_contactnum || '-';
        }
        
        if (data.medical_history) {
            document.getElementById('medical_history').value = data.medical_history;
        }
        
        if (data.recent_tests) {
            document.getElementById('tests_conducted').value = data.recent_tests;
        }
        
        if (data.current_medications) {
            document.getElementById('medications_given').value = data.current_medications;
        }
    })
    .catch(error => {
        console.error('Error loading appointment details:', error);
        clearPatientInfo();
    });
}

function clearPatientInfo() {
    document.getElementById('ref_pet_name').textContent = '-';
    document.getElementById('ref_pet_gender').textContent = '-';
    document.getElementById('ref_pet_dob').textContent = '-';
    document.getElementById('ref_pet_species').textContent = '-';
    document.getElementById('ref_pet_breed').textContent = '-';
    document.getElementById('ref_pet_weight').textContent = '-';
    document.getElementById('ref_owner_name').textContent = '-';
    document.getElementById('ref_owner_contact').textContent = '-';
    
    document.getElementById('medical_history').value = '';
    document.getElementById('tests_conducted').value = '';
    document.getElementById('medications_given').value = '';
}

// Referral form submission
document.getElementById('referralForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Validate required fields
    if (!formData.get('appointment_id')) {
        alert('Please select an appointment');
        return;
    }
    
    if (!formData.get('ref_date')) {
        alert('Please select a referral date');
        return;
    }
    
    if (!formData.get('ref_to')) {
        alert('Please select a branch to refer to');
        return;
    }
    
    if (!formData.get('ref_description')) {
        alert('Please provide a reason for referral');
        return;
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Submitting...';
    submitBtn.disabled = true;
    
    this.submit();
});

function editReferral(referralId) {
    fetch(`/medical-management/referrals/${referralId}/edit`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('referralForm').reset();
        document.getElementById('ref_id').value = referralId;
        document.getElementById('appointment_id').value = data.appointment_id;
        document.getElementById('ref_date').value = data.ref_date;
        document.getElementById('ref_to').value = data.ref_to;
        document.getElementById('ref_description').value = data.ref_description;
        document.getElementById('medical_history').value = data.medical_history || '';
        document.getElementById('tests_conducted').value = data.tests_conducted || '';
        document.getElementById('medications_given').value = data.medications_given || '';
        
        loadAppointmentDetails(data.appointment_id);
        
        document.getElementById('referralForm').action = `/medical-management/referrals/${referralId}`;
        document.getElementById('referralFormMethod').value = 'PUT';
        document.getElementById('referralModalTitle').textContent = 'Edit Referral';
        document.getElementById('referralModal').classList.remove('hidden');
    })
    .catch(error => {
        console.error('Error fetching referral data:', error);
        alert('Error loading referral data');
    });
}

function viewReferral(referralId) {
    fetch(`/medical-management/referrals/${referralId}`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Populate the form-style fields
        document.getElementById('view_ref_date').textContent = formatReferralDate(data.ref_date) || '-';
        document.getElementById('view_pet_name').textContent = (data.pet_name || '-').toUpperCase();
        document.getElementById('view_pet_gender').textContent = (data.pet_gender || '-').toUpperCase();
        document.getElementById('view_pet_dob').textContent = formatReferralDate(data.pet_dob) || '-';
        document.getElementById('view_owner_name').textContent = (data.owner_name || '-').toUpperCase();
        document.getElementById('view_owner_contact').textContent = data.owner_contact || '-';
        
        // Format medical history as a list
        document.getElementById('view_medical_history').innerHTML = formatListContent(data.medical_history, 'No medical history provided');
        
        // Format tests conducted
        document.getElementById('view_tests_conducted').innerHTML = formatListContent(data.tests_conducted, 'No tests documented');
        
        // Format medications given as a list
        document.getElementById('view_medications_given').innerHTML = formatListContent(data.medications_given, 'No medications documented');
        
        // Reason for referral
        document.getElementById('view_ref_description').textContent = data.ref_description || '-';
        
        // Branch information
        document.getElementById('view_ref_from_branch').textContent = (data.ref_by_branch || 'PETS 2GO VETERINARY CLINIC').toUpperCase();
        document.getElementById('view_ref_to_branch').textContent = (data.ref_to_branch || '-').toUpperCase();
        
        currentReferralId = referralId;
        document.getElementById('viewReferralModal').classList.remove('hidden');
    })
    .catch(error => {
        console.error('Error fetching referral details:', error);
        alert('Error loading referral details');
    });
}

function formatReferralDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).toUpperCase();
    } catch (e) {
        return dateString;
    }
}

function formatListContent(content, defaultText) {
    if (!content || content.trim() === '') {
        return `<em class="text-gray-500">${defaultText}</em>`;
    }
    
    // Split by common delimiters and create a list
    const items = content.split(/[;,\n]/).map(item => item.trim()).filter(item => item.length > 0);
    
    if (items.length > 1) {
        return '<ul class="history-list pl-0 list-none">' + 
               items.map(item => `<li class="mb-2 pl-5 relative before:content-['-'] before:absolute before:left-0 before:font-bold">${item}</li>`).join('') + 
               '</ul>';
    } else {
        return `<p class="mb-0">${content}</p>`;
    }
}

function printReferral(referralId) {
    // Fetch referral data for printing
    fetch(`/medical-management/referrals/${referralId}`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Create print content with the referral data
        const printContent = createReferralPrintContent(data);
        document.getElementById('printReferralContent').innerHTML = printContent;
        
        // Remove all print classes first
        document.getElementById('printContainer').classList.remove('print-prescription', 'print-referral');
        document.getElementById('printReferralContainer').classList.remove('print-prescription', 'print-referral');
        
        // Hide prescription container
        document.getElementById('printContainer').style.display = 'none';
        
        // Show referral container with print class
        const printContainer = document.getElementById('printReferralContainer');
        printContainer.style.display = 'block';
        printContainer.classList.add('print-referral');
        
        setTimeout(() => {
            window.print();
            printContainer.style.display = 'none';
            printContainer.classList.remove('print-referral');
        }, 200);
    })
    .catch(error => {
        console.error('Error fetching referral for printing:', error);
        alert('Error loading referral for printing');
    });
}

function createReferralPrintContent(data) {
    return `
        <!-- Header Section with Full Width Orange Background Container -->
        <div class="header mb-4 w-full">
            <div class="p-4 rounded-lg w-full" style="background-color: #f88e28;">
                <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" class="w-full h-auto object-contain" style="max-height: 120px; min-height: 80px;">
            </div>
        </div>
        
        <!-- Basic Information Section -->
        <div class="patient-info mb-3">
            <div class="grid grid-cols-3 gap-2 text-sm">
                <div>
                    <div class="mb-1"><strong>DATE:</strong> ${formatReferralDate(data.ref_date) || '-'}</div>
                    <div class="mb-1"><strong>NAME OF PET:</strong> ${(data.pet_name || '-').toUpperCase()}</div>
                </div>
                <div class="text-center">
                    <div><strong>OWNER:</strong> ${(data.owner_name || '-').toUpperCase()}</div>
                    <div><strong>CONTACT #:</strong> ${data.owner_contact || '-'}</div>
                </div>
                <div class="text-right">
                    <div><strong>DOB:</strong> ${formatReferralDate(data.pet_dob) || '-'}</div>
                    <div><strong>GENDER:</strong> ${(data.pet_gender || '-').toUpperCase()}</div>
                </div>
            </div>
        </div>
        
        <!-- History Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">HISTORY:</div>
            <div class="text-sm text-gray-700 mb-2">${formatListContent(data.medical_history, 'No medical history provided')}</div>
        </div>
        
        <!-- Test Conducted Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">TEST CONDUCTED:</div>
            <div class="text-sm text-gray-700 mb-2">${formatListContent(data.tests_conducted, 'No tests documented')}</div>
            <div class="test-note text-center font-bold text-gray-600 mt-2 text-sm italic">***NO FURTHER TESTS WERE PERFORMED***</div>
        </div>
        
        <!-- Medications Given Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">MEDS GIVEN:</div>
            <div class="text-sm text-gray-700 mb-2">${formatListContent(data.medications_given, 'No medications documented')}</div>
            <div class="med-note text-center font-bold text-gray-600 mt-2 text-sm italic">***NO OTHER MEDICATIONS GIVEN***</div>
        </div>
        
        <!-- Reason for Referral Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">REASON FOR REFERRAL:</div>
            <div class="text-sm text-gray-700 mb-2">${data.ref_description || '-'}</div>
        </div>
        
        <!-- Referring Information Section -->
        <div class="form-section mb-3">
            <div class="referral-info bg-gray-100 p-3 rounded border-l-4 border-orange-500">
                <div class="section-title font-bold text-sm text-gray-800 mb-2">REFERRING VETERINARIAN:</div>
                <div class="vet-name text-base font-bold text-gray-800 mb-1">DR. JAN JERICK M. GO</div>
                <div class="clinic-details text-sm text-gray-600 mb-1">LIC. NO. 0012045</div>
                <div class="clinic-details text-sm text-gray-600 mb-1">${(data.ref_by_branch || 'PETS 2GO VETERINARY CLINIC').toUpperCase()}</div>
                <div class="clinic-details text-sm text-gray-600">0906-765-9732</div>
            </div>
        </div>
        
        <!-- Referred To Section -->
        <div class="form-section">
            <div class="referral-info bg-blue-50 p-3 rounded border-l-4 border-blue-500">
                <div class="section-title font-bold text-sm text-gray-800 mb-2">REFERRED TO:</div>
                <div class="clinic-details text-base font-bold text-gray-800 mb-1">${(data.ref_to_branch || '-').toUpperCase()}</div>
                <div class="clinic-details text-sm text-gray-600 mb-1">Specialist Veterinary Care</div>
                <div class="clinic-details text-sm text-gray-600">For specialized treatment and consultation</div>
            </div>
        </div>
    `;
}

function closeViewReferralModal() {
    document.getElementById('viewReferralModal').classList.add('hidden');
}

// Form submission debugging for appointments
document.getElementById('addForm').addEventListener('submit', function(e) {
    const formData = new FormData(this);
    const services = formData.getAll('services[]');
    
    if (services.length === 0) {
        console.warn('No services found in form data!');
    }
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupCSRF();
    
    // Set active tab based on server-side data or default to appointments
    const activeTab = '{{ $activeTab ?? "appointments" }}';
    showTab(activeTab);
    
    // Set today's date as default for referral date
    const today = new Date().toISOString().split('T')[0];
    if (document.getElementById('ref_date')) {
        document.getElementById('ref_date').value = today;
    }
    if (document.getElementById('prescription_date')) {
        document.getElementById('prescription_date').value = today;
    }
    
    // Add form validation for appointment form
    const appointmentForm = document.getElementById('addForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            const petId = document.getElementById('pet_id').value;
            const appointDate = document.querySelector('input[name="appoint_date"]').value;
            const appointTime = document.querySelector('select[name="appoint_time"]').value;
            const appointStatus = document.querySelector('select[name="appoint_status"]').value;
            
            if (!petId) {
                e.preventDefault();
                alert('Please select a pet');
                return false;
            }
            
            if (!appointDate) {
                e.preventDefault();
                alert('Please select an appointment date');
                return false;
            }
            
            if (!appointTime) {
                e.preventDefault();
                alert('Please select an appointment time');
                return false;
            }
            
            if (!appointStatus) {
                e.preventDefault();
                alert('Please select an appointment status');
                return false;
            }
        });
    }
});

// Utility function to format dates
function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

// Utility function to show loading state
function showLoading(buttonElement, loadingText = 'Loading...') {
    if (!buttonElement) return;
    buttonElement.dataset.originalText = buttonElement.textContent;
    buttonElement.textContent = loadingText;
    buttonElement.disabled = true;
}

function hideLoading(buttonElement) {
    if (!buttonElement) return;
    buttonElement.textContent = buttonElement.dataset.originalText || 'Submit';
    buttonElement.disabled = false;
}

// Enhanced error handling for AJAX requests
function handleAjaxError(error, userMessage = 'An error occurred') {
    console.error('Error:', error);
    
    if (error.response) {
        error.response.json().then(data => {
            alert(data.message || userMessage);
        }).catch(() => {
            alert(userMessage);
        });
    } else {
        alert(userMessage);
    }
}

// Function to validate form data
function validateAppointmentForm(formData) {
    const required = ['pet_id', 'appoint_date', 'appoint_time', 'appoint_status', 'appoint_type'];
    const missing = required.filter(field => !formData.get(field));
    
    if (missing.length > 0) {
        alert(`Please fill in the following required fields: ${missing.join(', ')}`);
        return false;
    }
    
    return true;

     setupVaccineProductSearchListener('vacc_product_search', 'vacc_prod_id', 'vacc_product_suggestions');
}

function validatePrescriptionForm(medications, petId, prescriptionDate) {
    if (!petId) {
        alert('Please select a pet');
        return false;
    }
    
    if (!prescriptionDate) {
        alert('Please select a prescription date');
        return false;
    }
    
    if (medications.length === 0) {
        alert('Please add at least one medication');
        return false;
    }
    
    // Validate each medication has required fields
    for (let i = 0; i < medications.length; i++) {
        const med = medications[i];
        if (!med.product_name || !med.instructions) {
            alert(`Medication ${i + 1} is missing required information`);
            return false;
        }
    }
    
    return true;
}

function validateReferralForm(formData) {
    const required = ['appointment_id', 'ref_date', 'ref_to', 'ref_description'];
    const missing = required.filter(field => !formData.get(field));
    
    if (missing.length > 0) {
        alert(`Please fill in the following required fields: ${missing.join(', ')}`);
        return false;
    }
    
    return true;
}

// Auto-save functionality for forms (optional enhancement)
function enableAutoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    // Save form data to localStorage on input
    form.addEventListener('input', function(e) {
        if (e.target.type !== 'password') {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            localStorage.setItem(storageKey, JSON.stringify(data));
        }
    });
    
    // Restore form data on load
    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input && input.type !== 'password') {
                    input.value = data[key];
                }
            });
        } catch (e) {
            console.error('Error restoring form data:', e);
        }
    }
    
    // Clear saved data on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
    });
}

// Enhanced search functionality for modals
function setupModalSearch(inputSelector, resultsSelector, searchFunction) {
    const input = document.querySelector(inputSelector);
    const results = document.querySelector(resultsSelector);
    
    if (!input || !results) return;
    
    let searchTimeout;
    
    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            results.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchFunction(query, results);
        }, 300);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.add('hidden');
        }
    });
}

// Keyboard navigation for modals
function setupKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Close any open modals
            const modals = [
                'addModal', 'editModal', 'serviceSelectionModal', 
                'prescriptionModal', 'viewPrescriptionModal', 
                'referralModal', 'viewReferralModal'
            ];
            
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                    if (modal.classList.contains('flex')) {
                        modal.classList.remove('flex');
                    }
                }
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    setupKeyboardNavigation();
    
});

// ==================== VIEW APPOINTMENT FUNCTIONS ====================

function viewAppointment(appointmentId) {
    const url = `/medical-management/appointments/${appointmentId}/view`;
    console.log('Fetching URL:', url);
    
    fetch(url, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response:', response);
        
        // Get the error text if response is not ok
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response:', text);
                throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Received data:', data);
        const appt = data.appointment;
        
        // ... rest of your existing code
        document.getElementById('view_appoint_date').textContent = 
            new Date(appt.appoint_date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        
        document.getElementById('view_appoint_time').textContent = 
            new Date('2000-01-01 ' + appt.appoint_time).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        
        document.getElementById('view_appoint_type').textContent = appt.appoint_type || '-';
        
        const statusBadge = `<span class="px-3 py-1 rounded-full text-xs font-medium ${
            appt.appoint_status === 'completed' ? 'bg-green-100 text-green-800' : 
            appt.appoint_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
            appt.appoint_status === 'arrived' ? 'bg-blue-100 text-blue-800' :
            appt.appoint_status === 'refer' ? 'bg-purple-100 text-purple-800' : 
            'bg-gray-100 text-gray-800'
        }">
            ${appt.appoint_status.charAt(0).toUpperCase() + appt.appoint_status.slice(1)}
        </span>`;
        document.getElementById('view_appoint_status').innerHTML = statusBadge;
        
        document.getElementById('view_pet_name_appt').textContent = appt.pet?.pet_name || 'N/A';
        document.getElementById('view_owner_name_appt').textContent = appt.pet?.owner?.own_name || 'N/A';
        document.getElementById('view_owner_contact_appt').textContent = appt.pet?.owner?.own_contactnum || 'N/A';
        
        const servicesText = appt.services && appt.services.length > 0 
            ? appt.services.map(s => s.serv_name).join(', ')
            : 'No services assigned';
        document.getElementById('view_services').textContent = servicesText;
        
        document.getElementById('view_description').textContent = appt.appoint_description || 'No description provided';
        
        populateAppointmentHistory(data.history || []);
        
        document.getElementById('viewAppointmentModal').classList.remove('hidden');
    })
    .catch(error => {
        console.error('Full error:', error);
        alert('Error: ' + error.message);
    });
}

function populateAppointmentHistory(history) {
    const container = document.getElementById('appointment_history');
    
    if (!history || history.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-info-circle text-3xl mb-2"></i>
                <p>No history available for this appointment</p>
            </div>
        `;
        return;
    }
    
    // Reverse to show newest first
    const reversedHistory = [...history].reverse();
    
    container.innerHTML = reversedHistory.map((item, index) => {
        const isLast = index === reversedHistory.length - 1;
        const changeIcon = getChangeIcon(item.change_type);
        const changeColor = getChangeColor(item.change_type);
        
        return `
            <div class="relative ${isLast ? '' : 'pl-8 pb-6'}">
                ${!isLast ? '<div class="absolute left-3 top-8 bottom-0 w-0.5 bg-gray-300"></div>' : ''}
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full ${changeColor} flex items-center justify-center text-white shadow-md">
                        <i class="fas ${changeIcon} text-xs"></i>
                    </div>
                    <div class="flex-grow bg-white border rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2 flex-wrap gap-2">
                            <span class="font-semibold text-sm ${changeColor.replace('bg-', 'text-').replace('-500', '-700')} flex items-center gap-2">
                                ${formatChangeType(item.change_type)}
                            </span>
                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                <i class="fas fa-clock"></i>
                                ${formatDateTime(item.changed_at)}
                            </span>
                        </div>
                        ${formatChanges(item)}
                        <div class="mt-3 pt-3 border-t text-xs text-gray-600 flex items-center gap-2">
                            <i class="fas fa-user-circle"></i>
                            <span>Changed by: <strong>${item.changed_by}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getChangeIcon(changeType) {
    switch(changeType) {
        case 'created': return 'fa-plus-circle';
        case 'rescheduled': return 'fa-calendar-alt';
        case 'status_changed': return 'fa-exchange-alt';
        default: return 'fa-edit';
    }
}

function getChangeColor(changeType) {
    switch(changeType) {
        case 'created': return 'bg-green-500';
        case 'rescheduled': return 'bg-blue-500';
        case 'status_changed': return 'bg-purple-500';
        default: return 'bg-gray-500';
    }
}

function formatChangeType(changeType) {
    const types = {
        'created': 'Appointment Created',
        'rescheduled': 'Schedule Changed',
        'status_changed': 'Status Updated',
        'updated': 'Information Updated'
    };
    return types[changeType] || changeType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    try {
        return new Date(dateTimeString).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateTimeString;
    }
}

function formatChanges(item) {
    let html = '';
    
    if (item.old_data && Object.keys(item.old_data).length > 0) {
        html += '<div class="space-y-2">';
        
        // Date change
        if (item.old_data.date && item.new_data.date) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-calendar text-gray-400"></i>
                    <span class="text-red-600 line-through">${formatDate(item.old_data.date)}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium">${formatDate(item.new_data.date)}</span>
                </div>
            `;
        }
        
        // Time change
        if (item.old_data.time && item.new_data.time) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span class="text-red-600 line-through">${formatTime(item.old_data.time)}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium">${formatTime(item.new_data.time)}</span>
                </div>
            `;
        }
        
        // Status change
        if (item.old_data.status && item.new_data.status) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-flag text-gray-400"></i>
                    <span class="text-red-600 line-through capitalize">${item.old_data.status}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium capitalize">${item.new_data.status}</span>
                </div>
            `;
        }
        
        // Type change
        if (item.old_data.type && item.new_data.type) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-tag text-gray-400"></i>
                    <span class="text-red-600 line-through capitalize">${item.old_data.type}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium capitalize">${item.new_data.type}</span>
                </div>
            `;
        }
        
        html += '</div>';
    } else if (item.new_data) {
        // Initial creation
        html += '<div class="text-sm text-gray-700 space-y-1 bg-green-50 p-3 rounded">';
        html += '<div class="font-medium text-green-700 mb-2">Initial Appointment Details:</div>';
        
        if (item.new_data.date) {
            html += `<div><i class="fas fa-calendar mr-2 text-gray-500"></i>Date: <strong>${formatDate(item.new_data.date)}</strong></div>`;
        }
        if (item.new_data.time) {
            html += `<div><i class="fas fa-clock mr-2 text-gray-500"></i>Time: <strong>${formatTime(item.new_data.time)}</strong></div>`;
        }
        if (item.new_data.status) {
            html += `<div><i class="fas fa-flag mr-2 text-gray-500"></i>Status: <strong class="capitalize">${item.new_data.status}</strong></div>`;
        }
        if (item.new_data.type) {
            html += `<div><i class="fas fa-tag mr-2 text-gray-500"></i>Type: <strong class="capitalize">${item.new_data.type}</strong></div>`;
        }
        
        html += '</div>';
    } else {
        html += `<div class="text-sm text-gray-600 italic">${item.notes || 'No details available'}</div>`;
    }
    
    return html;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    } catch (e) {
        return dateString;
    }
}

function formatTime(timeString) {
    if (!timeString) return '-';
    try {
        return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    } catch (e) {
        return timeString;
    }
}

function closeViewAppointmentModal() {
    document.getElementById('viewAppointmentModal').classList.add('hidden');
}
</script>

@endsection
<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BiometricDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $devices = BiometricDevice::orderBy('name')->paginate(20);

        return view('biometric.devices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('biometric.devices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'serial_number' => 'required|string|max:255|unique:biometric_devices,serial_number',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('biometric-devices.create')
                ->withErrors($validator)
                ->withInput();
        }

        BiometricDevice::create($request->only(['serial_number', 'name', 'location']));

        return redirect()->route('biometric-devices.index')
            ->with('success', 'Device registered successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BiometricDevice $biometricDevice)
    {
        return view('biometric.devices.edit', ['device' => $biometricDevice]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BiometricDevice $biometricDevice)
    {
        $validator = Validator::make($request->all(), [
            'serial_number' => 'required|string|max:255|unique:biometric_devices,serial_number,' . $biometricDevice->id,
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('biometric-devices.edit', $biometricDevice)
                ->withErrors($validator)
                ->withInput();
        }

        $biometricDevice->update($request->only(['serial_number', 'name', 'location']));

        return redirect()->route('biometric-devices.index')
            ->with('success', 'Device updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BiometricDevice $biometricDevice)
    {
        $biometricDevice->delete();

        return redirect()->route('biometric-devices.index')
            ->with('success', 'Device removed successfully.');
    }
}

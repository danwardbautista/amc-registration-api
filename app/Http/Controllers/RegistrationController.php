<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    public function getAllRegistrants(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\s@.\-_+()]*$/',
                'sort_by' => 'nullable|string|max:50|alpha_dash',
                'sort_order' => 'nullable|string|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Invalid input parameters'
                ], 422);
            }

            $allowedSorts = [
                'id',
                'first_name',
                'last_name',
                'mobile_number',
                'email',
                'created_at',
                'updated_at'
            ];

            $query = Personnel::query();

            // Sanitize search filter
            if ($request->filled('search')) {
                $search = $this->sanitizeSearch($request->input('search'));
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile_number', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });

                    Log::info('Personnel search performed', [
                        'user_id' => Auth::id(),
                        'search_length' => strlen($search),
                        'ip_address' => $request->ip(),
                        'timestamp' => now()
                    ]);
                }
            }

            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'created_at';

                // Log suspicious sort attempts
                Log::warning('Invalid sort field attempted', [
                    'attempted_sort' => $request->input('sort_by'),
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip()
                ]);
            }

            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
            $perPage = min((int) $request->input('per_page', 10), 100);

            $query->orderBy($sortBy, $sortOrder);
            $personnel = $query->paginate($perPage);

            // Log access
            Log::info('Personnel data accessed', [
                'user_id' => Auth::id(),
                'records_count' => $personnel->count(),
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'results' => $personnel
            ], 200);
        } catch (\Exception $e) {
            Log::error('Personnel retrieval failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Unable to retrieve data'
            ], 500);
        }
    }

    public function getRegistrantById(Request $request, $id)
    {
        try {
            $personnel = Personnel::where('id', $id)->whereNull('deleted_at')->first();

            if (!$personnel) {
                Log::warning('Registrant not found', [
                    'personnel_id' => $id,
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'timestamp' => now()
                ]);

                return response()->json([
                    'error' => 'Registrant not found']
                    , 404);
            }

            $personnel->logSensitiveAccess('view');

            return response()->json(['data' => $personnel], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving registrant by ID', [
                'personnel_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Unable to retrieve registrant'
            ], 500);
        }
    }


    public function createRegistrant(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), Personnel::rules());

            if ($validator->fails()) {
                Log::warning('Personnel creation validation failed', [
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'failed_fields' => array_keys($validator->errors()->toArray()),
                    'timestamp' => now()
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'Please check your input data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personnel = Personnel::create($request->only([
                'prefix',
                'first_name',
                'last_name',
                'mobile_number',
                'email'
            ]));

            Log::info('Personnel created via API', [
                'personnel_id' => $personnel->id,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'timestamp' => now()
            ]);

            return response()->json([
                'data' => $personnel,
                'message' => 'Registrant created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Personnel creation failed - general error', [
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'error_message' => $e->getMessage(),
                'timestamp' => now()
            ]);

            return response()->json([
                'error' => 'Creation failed',
                'message' => 'Unable to create registrant'
            ], 500);
        }
    }

    public function updateRegistrant(Request $request, $id)
    {
        try {
            // Retrieve
            $personnel = Personnel::where('id', $id)->whereNull('deleted_at')->first();

            if (!$personnel) {
                Log::warning('Personnel update failed - not found', [
                    'personnel_id' => $id,
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'timestamp' => now()
                ]);

                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Registrant does not exist'
                ], 404);
            }

            $validator = Validator::make($request->all(), Personnel::updateRules($id));

            if ($validator->fails()) {
                Log::warning('Personnel update validation failed', [
                    'personnel_id' => $id,
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'failed_fields' => array_keys($validator->errors()->toArray()),
                    'timestamp' => now()
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'Please check your input data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personnel->fill($request->only([
                'prefix',
                'first_name',
                'last_name',
                'mobile_number',
                'email'
            ]));

            $personnel->save();

            Log::info('Personnel updated via API', [
                'personnel_id' => $personnel->id,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'timestamp' => now()
            ]);

            return response()->json([
                'data' => $personnel,
                'message' => 'Registrant updated successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Personnel update failed - general error', [
                'personnel_id' => $id,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'error_class' => get_class($e),
                'timestamp' => now()
            ]);

            return response()->json([
                'error' => 'Update failed',
                'message' => 'Unable to update registrant'
            ], 500);
        }
    }

    public function deleteRegistrant(Request $request, $id)
    {
        try {
            $personnel = Personnel::where('id', $id)->whereNull('deleted_at')->first();

            if (!$personnel) {
                Log::warning('Personnel deletion failed - not found', [
                    'personnel_id' => $id,
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'timestamp' => now()
                ]);

                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Registrant does not exist'
                ], 404);
            }

            $personnel->delete();

            Log::info('Personnel deleted via API', [
                'personnel_id' => $personnel->id,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'timestamp' => now()
            ]);

            return response()->json([
                'message' => 'Registrant deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Personnel deletion failed - general error', [
                'personnel_id' => $id,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'error_class' => get_class($e),
                'timestamp' => now()
            ]);

            return response()->json([
                'error' => 'Deletion failed',
                'message' => 'Unable to delete registrant'
            ], 500);
        }
    }

    private function sanitizeSearch($search)
    {
        if (empty($search)) return null;

        $search = trim(strip_tags($search));
        $search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');

        // Remove dangerous patterns
        $search = preg_replace('/\b(union|select|insert|update|delete)\b/i', '', $search);
        $search = preg_replace('/[<>"\']/', '', $search);

        return trim($search);
    }
}

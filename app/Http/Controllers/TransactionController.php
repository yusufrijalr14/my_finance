<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => ['string', 'nullable'],
                'sort_by' => ['required_with:sort_order', Rule::in('id', 'total')],
                'sort_order' => ['required_with:sort_by', Rule::in('asc', 'desc')],
                'per_page' => ['numeric'],
                'type' => ['numeric', Rule::in(1, 2)]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $search = $request->search;
            $sortBy = $request->sort_by;
            $sortOrder = $request->sort_order;
            $perPage = $request->per_page;
            $type = $request->type;
            $userId = Auth::guard('user')->user()->id;

            $transaction = Transaction::where('user_id', $userId)
                ->when($type, function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($sortBy && $sortOrder, function ($query) use ($sortBy, $sortOrder) {
                    $query->orderBy($sortBy, $sortOrder);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->paginate($perPage);
            
            return response()->json([
                'status' => 200,
                'message' => 'Transactions list',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TransactionRequest $request)
    {
        try {
            $transaction = new Transaction();
            $transaction->user_id = Auth::guard('user')->user()->id;
            $transaction->type = $request->type;
            $transaction->name = $request->name;
            $transaction->total = $request->total;
            $transaction->save();
            
            return response()->json([
                'status' => 200,
                'message' => 'Transaction has been successfully created',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TransactionRequest $request, $id)
    {
        try {
            $transaction = Transaction::find($id);
            $transaction->type = $request->type;
            $transaction->name = $request->name;
            $transaction->total = $request->total;
            $transaction->save();
            
            return response()->json([
                'status' => 200,
                'message' => 'Transaction has been successfully updated',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $transaction = Transaction::find($id);
            
            $transaction->delete();
            
            return response()->json([
                'status' => 200,
                'message' => 'Transaction has been successfully deleted',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function incomeSummary(Request $request, $forLeft = false)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => ['required', Rule::in('today', 'this_month', 'custom')],
                'start_date' => ['required_if:type,custom'],
                'end_date' => ['required_if:type,custom']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $type = $request->type;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $userId = Auth::guard('user')->user()->id;

            $transaction = Transaction::where('type', 1)
                ->where('user_id', $userId)
                ->when($type, function ($query) use ($type, $startDate, $endDate) {
                    if ($type == 'today') {
                        $query->whereDate('created_at', Carbon::now());
                    } elseif ($type == 'this_month') {
                        $query->whereMonth('created_at', Carbon::now());
                    } else {
                        $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                    }
                });

            if ($forLeft) {
                return $transaction->sum('total');
            }
            
            return response()->json([
                'status' => 200,
                'message' => 'Income summary',
                'total' => $transaction->sum('total'),
                'results' => $transaction->get()
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function expenseSummary(Request $request, $forLeft = false)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => ['required', Rule::in('today', 'this_month', 'custom')],
                'start_date' => ['required_if:type,custom'],
                'end_date' => ['required_if:type,custom']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $type = $request->type;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $userId = Auth::guard('user')->user()->id;

            $transaction = Transaction::where('type', 2)
                ->where('user_id', $userId)
                ->when($type, function ($query) use ($type, $startDate, $endDate) {
                    if ($type == 'today') {
                        $query->whereDate('created_at', Carbon::now());
                    } elseif ($type == 'this_month') {
                        $query->whereMonth('created_at', Carbon::now());
                    } else {
                        $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                    }
                });
            
            if ($forLeft) {
                return $transaction->sum('total');
            }

            return response()->json([
                'status' => 200,
                'message' => 'Expense Summary',
                'total' => $transaction->sum('total'),
                'results' => $transaction->get()
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function highest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => ['required', Rule::in(1, 2)],
                'time' => ['nullable', Rule::in('today', 'this_month', 'custom')],
                'start_date' => ['required_if:time,custom'],
                'end_date' => ['required_if:time,custom']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $type = $request->type;
            $time = $request->time;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $userId = Auth::guard('user')->user()->id;

            $transaction = Transaction::where('type', $type)
                ->where('user_id', $userId)
                ->when($time, function ($query) use ($time, $startDate, $endDate) {
                    if ($time == 'today') {
                        $query->whereDate('created_at', Carbon::now());
                    } elseif ($time == 'this_month') {
                        $query->whereMonth('created_at', Carbon::now());
                    } else {
                        $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                    }
                })
                ->max('total');
            
            return response()->json([
                'status' => 200,
                'message' => $type == 1 ? 'Highest income' : 'Highest expense',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function left(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => ['required', Rule::in('today', 'this_month', 'custom')],
                'start_date' => ['required_if:type,custom'],
                'end_date' => ['required_if:type,custom']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $income = $this->incomeSummary($request, true);
            $expense = $this->expenseSummary($request, true);
            $transaction = $income - $expense;
            
            return response()->json([
                'status' => 200,
                'message' => 'Left in a month',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function trash(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => ['string', 'nullable'],
                'sort_by' => ['required_with:sort_order', Rule::in('id', 'total')],
                'sort_order' => ['required_with:sort_by', Rule::in('asc', 'desc')],
                'per_page' => ['numeric'],
                'type' => ['numeric', Rule::in(1, 2)]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }

            $search = $request->search;
            $sortBy = $request->sort_by;
            $sortOrder = $request->sort_order;
            $perPage = $request->per_page;
            $type = $request->type;
            $userId = Auth::guard('user')->user()->id;

            $transaction = Transaction::onlyTrashed()
                ->where('user_id', $userId)
                ->when($type, function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($sortBy && $sortOrder, function ($query) use ($sortBy, $sortOrder) {
                    $query->orderBy($sortBy, $sortOrder);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->paginate($perPage);
            
            return response()->json([
                'status' => 200,
                'message' => 'Transactions trash list',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function restore($id)
    {
        try {
            $transaction = Transaction::onlyTrashed()->find($id);
            $transaction->restore();
            
            return response()->json([
                'status' => 200,
                'message' => 'Transaction has been successfully restored',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function restores($type)
    {
        try {
            $transaction = Transaction::onlyTrashed()->where('type', $type);
            $transaction->restore();
            
            return response()->json([
                'status' => 200,
                'message' => 'Transaction has been successfully restored',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function forceDelete($id)
    {
        try {
            $transaction = Transaction::onlyTrashed()->find($id);
            $transaction->forceDelete();
            
            return response()->json([
                'status' => 200,
                'message' => 'Transaction has been successfully deleted',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function forceDeletes($type)
    {
        try {
            $transaction = Transaction::onlyTrashed()->where('type', $type);
            $transaction->forceDelete();
            
            return response()->json([
                'status' => 200,
                'message' => 'Transaction has been successfully deleted',
                'results' => $transaction
            ], 200);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
}

<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Http\Requests\StoreTeamRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateTeamRequest;
use RayzenAI\ProjectManagement\Http\Resources\MemberResource;
use RayzenAI\ProjectManagement\Http\Resources\TeamResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Team;

class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::query()->withCount('members')->with('members:id')->orderBy('name')->get();
        $members = Member::query()->with('teams:id')->orderBy('name')->get();

        $users = config('project-management.user_model', User::class)::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Team/Index', [
            'teams' => TeamResource::collection($teams)->resolve(),
            'members' => MemberResource::collection($members)->resolve(),
            'users' => $users->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = Team::create($request->safe()->except('member_ids'));

        $team->members()->sync($request->validated('member_ids', []));

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Team created.']);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->safe()->except('member_ids'));

        if ($request->has('member_ids')) {
            $team->members()->sync($request->validated('member_ids', []));
        }

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Team updated.']);
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Team deleted.']);
    }
}

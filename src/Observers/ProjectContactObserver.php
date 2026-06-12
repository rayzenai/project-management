<?php

namespace RayzenAI\ProjectManagement\Observers;

use RayzenAI\ProjectManagement\Models\ProjectActivity;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Services\ProjectActivityRecorder;

class ProjectContactObserver
{
    public function created(ProjectContact $contact): void
    {
        $name = (string) $contact->name;
        $org = (string) $contact->organization;

        ProjectActivityRecorder::record(
            taskId: $contact->task_id,
            subject: $contact,
            action: ProjectActivity::ACTION_CREATED,
            description: 'Contact added: '.$name.($org !== '' ? '@'.$org : ''),
        );
    }

    public function updated(ProjectContact $contact): void
    {
        ProjectActivityRecorder::record(
            taskId: $contact->task_id,
            subject: $contact,
            action: ProjectActivity::ACTION_UPDATED,
            description: 'Contact '.((string) $contact->name).' edited',
        );
    }

    public function deleted(ProjectContact $contact): void
    {
        ProjectActivityRecorder::record(
            taskId: $contact->task_id,
            subject: $contact,
            action: ProjectActivity::ACTION_DELETED,
            description: 'Contact '.((string) $contact->name).' removed',
        );
    }
}

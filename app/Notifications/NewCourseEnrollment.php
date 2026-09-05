<?php

namespace App\Notifications;

use App\Models\CourseEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCourseEnrollment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CourseEnrollment $enrollment
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Beautician Course Enrollment: ' . $this->enrollment->course?->title)
            ->greeting('Hello ' . $notifiable->name)
            ->line('A new student has enrolled in your beauty course.')
            ->line('Course: ' . ($this->enrollment->course?->title ?? 'Course'))
            ->line('Student: ' . $this->enrollment->student_name)
            ->line('Phone: ' . $this->enrollment->student_phone)
            ->action('View Enrollments', route('artist.courses.enrollments'))
            ->line('Please review student details and prepare their batch placement.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Course Student Enrollment',
            'message' => $this->enrollment->student_name . ' enrolled in "' . ($this->enrollment->course?->title ?? 'Course') . '"',
            'type' => 'success',
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $this->enrollment->course_id,
        ];
    }
}

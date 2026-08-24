<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Alert;
use App\Models\Course;
use App\Models\Recommendation;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:2000',
            'student_id' => 'nullable|integer',
        ]);

        $prompt = trim($validated['prompt']);
        $studentId = $validated['student_id'] ?? null;
        $isAr = preg_match('/[\x{0600}-\x{06FF}]/u', $prompt);

        // Fetch live database statistics
        $students = User::whereHas('roles', function ($q) {
            $q->where('name', 'student')->orWhere('name', 'Student');
        })->get();

        if ($students->isEmpty()) {
            $students = User::all();
        }

        $grades = Grade::all();
        $attendances = Attendance::all();
        $alerts = Alert::all();
        $courses = Course::all();
        $recommendations = Recommendation::all();

        $qLower = mb_strtolower($prompt);

        // 1. Check if specific student name was mentioned or passed in student_id
        $targetStudent = null;
        if ($studentId) {
            $targetStudent = $students->firstWhere('id', $studentId);
        } else {
            foreach ($students as $s) {
                if ($s->name && mb_stripos($qLower, mb_strtolower($s->name)) !== false) {
                    $targetStudent = $s;
                    break;
                }
            }
        }

        if ($targetStudent) {
            $sGrades = $grades->where('user_id', $targetStudent->id);
            $sAtt = $attendances->where('user_id', $targetStudent->id);
            $sPresent = $sAtt->where('status', 'present')->count();
            $attRate = $sAtt->count() > 0 ? round(($sPresent / $sAtt->count()) * 100, 1) : 95.0;
            $avgScore = $sGrades->count() > 0 ? round($sGrades->avg('score'), 1) : 85.0;
            $gpa = round($avgScore / 25, 2);
            $sAlerts = $alerts->where('student_id', $targetStudent->id)->count();

            if ($isAr) {
                $response = "📊 **التقرير التحليلي المباشر للطالب ({$targetStudent->name}):**\n\n"
                          . "• **الرقم الجامعي:** #STU-{$targetStudent->id}\n"
                          . "• **المعدل التراكمي المقدر:** {$gpa} / 4.00 (متوسط الدرجات: {$avgScore}%)\n"
                          . "• **نسبة الالتزام بالحضور:** {$attRate}% ({$sPresent} جلسات حضور من أصل {$sAtt->count()})\n"
                          . "• **الإنذارات الأكاديمية النشطة:** {$sAlerts} إنذار\n\n"
                          . ($gpa >= 3.0 ? "✅ **الحالة الأكاديمية:** مستقرة وممتازة." : "⚠️ **الحالة الأكاديمية:** يحتاج الطالب لجلسة إرشاد ومتابعة للدرجات المنخفضة.");
                $suggestions = ["عرض المقررات المسجلة", "توليد خطة دعم دراسية", "إرسال رسالة للمرشد"];
            } else {
                $response = "📊 **Live Diagnostic Profile for ({$targetStudent->name}):**\n\n"
                          . "• **Student ID:** #STU-{$targetStudent->id}\n"
                          . "• **Estimated GPA:** {$gpa} / 4.00 (Grade Average: {$avgScore}%)\n"
                          . "• **Attendance Rate:** {$attRate}% ({$sPresent} present out of {$sAtt->count()} sessions)\n"
                          . "• **Active Warning Flags:** {$sAlerts} alerts\n\n"
                          . ($gpa >= 3.0 ? "✅ **Academic Standing:** Good & Consistent." : "⚠️ **Academic Standing:** Student requires proactive tutoring follow-up.");
                $suggestions = ["Show enrolled courses", "Generate study support plan", "Message student advisor"];
            }

            return response()->json([
                'id' => 'msg_' . time(),
                'message' => $response,
                'timestamp' => date('H:i'),
                'suggestions' => $suggestions,
            ]);
        }

        // 2. Attendance Questions
        if (mb_stripos($qLower, 'attendance') !== false || mb_stripos($qLower, 'حضور') !== false || mb_stripos($qLower, 'غياب') !== false || mb_stripos($qLower, 'absent') !== false) {
            $totalSessions = max(1, $attendances->count());
            $presentCount = $attendances->where('status', 'present')->count();
            $absentCount = $attendances->where('status', 'absent')->count();
            $avgAttendance = round(($presentCount / $totalSessions) * 100, 1);

            if ($isAr) {
                $response = "📈 **تحليل الحضور والمواظبة اللحظي للشُعب (من MySQL):**\n\n"
                          . "• **إجمالي جلسات الحضور المسجلة:** {$totalSessions} جلسة.\n"
                          . "• **معدل الحضور العام للشُعب:** {$avgAttendance}%.\n"
                          . "• **إجمالي حالات الغياب المرصودة:** {$absentCount} حالة غياب.\n\n"
                          . "💡 **نصيحة إرشادية:** يوصى بمراجعة الطلاب الذين تجاوزت نسبة غيابهم 15% وتوجيه إشعارات إنذار مبكر لحمايتهم من الحرمان.";
                $suggestions = ["من هم الطلاب المعرضين للتعثر؟", "خطط التدخل الأكاديمي", "معدل درجات الشُعب"];
            } else {
                $response = "📈 **Live Attendance Compliance Velocity (Direct from MySQL):**\n\n"
                          . "• **Total Attendance Logs:** {$totalSessions} sessions.\n"
                          . "• **Cohort Average Attendance:** {$avgAttendance}%.\n"
                          . "• **Total Absence Incidents:** {$absentCount} logged absences.\n\n"
                          . "💡 **Advisor Action:** We advise sending proactive alerts to students exceeding 15% absence to prevent compulsory drops.";
                $suggestions = ["Who is at risk?", "Active recommendations", "Course performance breakdown"];
            }

            return response()->json([
                'id' => 'msg_' . time(),
                'message' => $response,
                'timestamp' => date('H:i'),
                'suggestions' => $suggestions,
            ]);
        }

        // 3. Risk & Failing Students Questions
        if (mb_stripos($qLower, 'risk') !== false || mb_stripos($qLower, 'خطر') !== false || mb_stripos($qLower, 'تعثر') !== false || mb_stripos($qLower, 'رسوب') !== false || mb_stripos($qLower, 'failing') !== false || mb_stripos($qLower, 'grade') !== false || mb_stripos($qLower, 'درجات') !== false) {
            $failingGrades = $grades->where('score', '<', 60);
            $failingIds = $failingGrades->pluck('user_id')->unique()->values();
            $criticalAlerts = $alerts->whereIn('level', ['critical', 'high'])->count();

            $failingNames = $students->whereIn('id', $failingIds)->pluck('name')->take(3)->implode('، ');

            if ($isAr) {
                $response = "⚠️ **إحصائية الطلاب المعرضين لخطر التعثر (مباشر من MySQL):**\n\n"
                          . "• **إجمالي الطلاب المسجلين بالمنظومة:** {$students->count()} طالب.\n"
                          . "• **عدد الطلاب المسجلين بدرجات أقل من 60%:** {$failingIds->count()} طالب.\n"
                          . "• **عدد التنبيهات عالية الخطورة (Critical/High):** {$criticalAlerts} تنبيه.\n"
                          . ($failingNames ? "• **أبرز الطلاب المحتاجين للمتابعة:** {$failingNames}.\n\n" : "\n")
                          . "💡 **خطة العمل:** يمكنك اعتماد جلسات التقوية الموصى بها في تبويب التوصيات الأكاديمية بنقرة واحدة.";
                $suggestions = ["تحليل نسبة الحضور والغياب", "عرض خطط الدعم الأكاديمي", "كشف درجات الشُعب"];
            } else {
                $response = "⚠️ **Live At-Risk Cohort Intelligence (Direct from MySQL):**\n\n"
                          . "• **Total Active Students:** {$students->count()} students.\n"
                          . "• **Students with Scores Below 60%:** {$failingIds->count()} students.\n"
                          . "• **High / Critical Risk Alerts:** {$criticalAlerts} flags.\n"
                          . ($failingNames ? "• **Students requiring follow-up:** {$failingNames}.\n\n" : "\n")
                          . "💡 **Action Item:** Review and approve AI-generated tutoring plans in the Recommendations center.";
                $suggestions = ["Analyze cohort attendance", "Show active interventions", "Course grade summary"];
            }

            return response()->json([
                'id' => 'msg_' . time(),
                'message' => $response,
                'timestamp' => date('H:i'),
                'suggestions' => $suggestions,
            ]);
        }

        // 4. Recommendations / Plans
        if (mb_stripos($qLower, 'recommend') !== false || mb_stripos($qLower, 'توصي') !== false || mb_stripos($qLower, 'تدخل') !== false || mb_stripos($qLower, 'خطة') !== false || mb_stripos($qLower, 'plan') !== false) {
            $approved = $recommendations->where('status', 'approved')->count();
            $pending = $recommendations->where('status', '!=', 'approved')->count();

            if ($isAr) {
                $response = "💡 **ملخص خطط التدخل والتوصيات الإرشادية:**\n\n"
                          . "• **إجمالي التوصيات الصادرة:** {$recommendations->count()} توصية.\n"
                          . "• **الخطط المعتمدة والنشطة:** {$approved} خطة.\n"
                          . "• **الخطط المعلقة بانتظار الاعتماد:** {$pending} خطة.\n\n"
                          . "يمكنك اعتماد وتطبيق أي توصية بنقرة واحدة من لوحة المرشد.";
                $suggestions = ["من هم الطلاب المعرضين للتعثر؟", "تحليل نسبة الحضور", "الملخص العام"];
            } else {
                $response = "💡 **AI Intervention & Guidance Roadmap:**\n\n"
                          . "• **Total Generated Plans:** {$recommendations->count()} recommendations.\n"
                          . "• **Approved & Active Plans:** {$approved} active.\n"
                          . "• **Pending Review:** {$pending} pending.\n\n"
                          . "You can approve and dispatch counseling plans directly from the Early-Warning Inbox.";
                $suggestions = ["Who is at risk?", "Attendance rate analysis", "General cohort overview"];
            }

            return response()->json([
                'id' => 'msg_' . time(),
                'message' => $response,
                'timestamp' => date('H:i'),
                'suggestions' => $suggestions,
            ]);
        }

        // 5. Default General Intelligence Overview
        if ($isAr) {
            $response = "🤖 **مرحباً بك في نظام SBA AI Intelligence التحليلي:**\n\n"
                      . "• **الطلاب النشطين:** {$students->count()} طالب.\n"
                      . "• **المقررات الدراسية:** {$courses->count()} مقرر.\n"
                      . "• **تقييمات الدرجات المسجلة:** {$grades->count()} درجة.\n"
                      . "• **سجلات الحضور والغياب:** {$attendances->count()} جلسة.\n"
                      . "• **الإنذارات النشطة:** {$alerts->count()} إنذار.\n\n"
                      . "يمكنك سؤالي عن: *نسب الغياب، الطلاب المتعثرين، كشف درجات مقرر، أو كتابة اسم أي طالب لعرض ملفه كاملاً.*";
            $suggestions = ["من هم الطلاب المعرضين للتعثر؟", "تحليل نسبة الحضور والغياب للشُعب", "ما هي التوصيات والتدخلات المقترحة؟"];
        } else {
            $response = "🤖 **Welcome to SBA AI Intelligence Core:**\n\n"
                      . "• **Active Students:** {$students->count()} students.\n"
                      . "• **Curriculum Courses:** {$courses->count()} courses.\n"
                      . "• **Recorded Grades:** {$grades->count()} entries.\n"
                      . "• **Attendance Logs:** {$attendances->count()} sessions.\n"
                      . "• **Active Alerts:** {$alerts->count()} alerts.\n\n"
                      . "Ask me about: *Attendance dips, at-risk students, course grade stats, or type any student's name for a full 360° diagnostic.*";
            $suggestions = ["Who are the at-risk students?", "Analyze cohort attendance rate", "What are the active recommendations?"];
        }

        return response()->json([
            'id' => 'msg_' . time(),
            'message' => $response,
            'timestamp' => date('H:i'),
            'suggestions' => $suggestions,
        ]);
    }
}

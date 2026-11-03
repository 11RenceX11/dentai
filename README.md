# DentAI

**A smart dental clinic scheduling and triage platform.**

Patients book online and describe their problem. Dentists and assistants arrange the schedule, and a built-in assistant proposes prioritised next steps that a human approves before anything is added to the appointment.

---

## About the Project

Dental clinics juggle a constant flow of appointment requests, from routine check-ups to painful emergencies. Front-desk staff often have to read every request, judge how urgent it is, and decide what the patient might need, all before a single slot is booked.

DentAI makes that first step faster and more consistent. Patients describe what is wrong in their own words, and the system helps the clinic quickly see **who needs attention first** and **what should happen next**, while keeping dentists and assistants firmly in control of every decision.

## The Problem It Solves

- **Unclear urgency.** A booking request rarely says how serious the situation is. Staff have to guess or call back.
- **Slow, manual intake.** Collecting symptoms, pain levels and background by phone takes time away from patient care.
- **Inconsistent follow-up.** Whether a patient gets an X-ray, an allergy check or a same-day call can depend on who handled the request.
- **Scattered records.** Appointments, treatments, prescriptions, X-rays and billing are often spread across different tools.

## Who It's For

| User | Their experience |
|------|------------------|
| **Patients** | Book without creating an account. Share their name, contact details, the type of problem, how it feels, and a pain rating from 0 to 10. |
| **Assistants** | Work from a prioritised queue, arrange schedules, review and approve suggestions, manage patient records and handle billing. |
| **Dentists** | Get the same clinical view, plus tools for treatment notes and prescriptions. Billing is kept separate. |
| **Admins** | Full oversight, including management of the clinic's treatment catalogue. |

## Key Ideas

### Patient-first booking
A simple online request form that asks the right questions, so the clinic gets useful information from the very first contact.

### Assisted triage, with a human in the loop
Each request is assessed for urgency (low, normal, high or emergency) using the type of problem, the reported pain level, and warning signs in the patient's description, such as swelling or fever. The assistant then proposes actions like adding a treatment, requesting an X-ray, calling the patient the same day, checking allergies first, or booking by a target date.

**Nothing happens automatically.** Every suggestion waits for a dentist or assistant to approve or dismiss it. The assistant supports clinical judgment; it never replaces it.

### One place for the whole patient journey
Appointments, treatment plans, prescriptions, X-ray images, billing and payments all live together, so staff see the full picture without switching tools.

### Role-based access
Each person sees what they need and nothing more. Patients book, assistants coordinate, dentists treat, and admins manage.

## Current Status

DentAI is an early-stage project. The assistant is rule-based rather than powered by a language model, which keeps its behaviour predictable and easy to explain. It is designed so a more advanced AI layer can be introduced later.

The landing page uses placeholder branding, pricing and FAQ content that should be replaced before any public launch.

## Vision

To give small and mid-sized dental practices a calm, organised front desk: fewer missed emergencies, less repetitive admin, and more time spent on patients.

## Built With

Laravel, Blade, Tailwind CSS and SQLite.

## Contributing

Ideas, feedback and improvements are welcome. Please open an issue to start a conversation.

## License

To be decided by the project owner.

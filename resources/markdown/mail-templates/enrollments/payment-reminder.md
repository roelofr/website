---
label: enroll-payment-reminder
subject: Je moet nog betalen voor {activity}
footnote: |
    Deze betalingsherrinnering is verstuurd omdat je je hebt aangemeld voor {activity} en hierbij aangepaste
    afspraken hebt gemaakt over de betaling. Wil je hier iets aan wijzigen, neem dan contact op met {host}.
    Je kan je inschrijving beheren via {enrollment_link}.
parameters:
    - name: activity
      description: Naam van de activiteit
      
    - name: first_name
      description: Voornaam
      
    - name: name
      description: Volledige naam
      
    - name: start_date
      description: Aanvangstdatum
      
    - name: start_time
      description: Aanvangsttijd

    - name: activity_link
      description: Link naar de activiteit

    - name: enrollment_link
      description: Link naar de inschrijving

    - name: host
      description: Naam organisatie
...

Beste {first_name},

Bedankt voor je inschrijving voor [{activity}][1]. Deze mail dienst als bevestiging dat je inschrijving goed is ontvangen.

Je kunt nu [je inschrijving beheren][2] en [je tickets downloaden][2] op [de website][2].

Namens {host},
De Digitale Commissie.

[1]: {activity_link}
[2]: {enrollment_link}


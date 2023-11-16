---
label: enrollment-cancelled-timeout
subject: Inschrijving verlopen
footnote: |
    Deze servicemail is verstuurd omdat je je hebt aangemeld voor {activity}.
    Dit is het laatste bericht over deze activiteit, tenzij je je weer opnieuw aanmeldt.
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

    - name: host
      description: Naam organisatie
...

Beste {first_name},

Je bent begonnen met je aan te melden voor [{activity}][1], maar hebt deze inschrijving niet binnen de gestelde tijd
afgerond. Om plek vrij te houden voor andere deelnemers, is je inschrijving weer verwijderd.

Indien er nog plek is (en je nog wil), kan je je [opnieuw aanmelden][1].

Namens {host},
De Digitale Commissie.

[1]: {activity_link}

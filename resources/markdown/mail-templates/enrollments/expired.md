---
label: enrollment-expired
subject: Uitgeschreven voor {activity}
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

je bent begonnen met het aanmelden voor [{activity}][1], maar hebt dit niet binnen de gestelde tijd afgemaakt.

Om plek vrij te houden voor anderen, is je inschrijving weer verwijderd.

Namens {host},
De Digitale Commissie.

[1]: {activity_link}

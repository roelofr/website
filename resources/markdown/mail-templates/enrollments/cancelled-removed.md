---
label: enrollment-cancelled-removed
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

Je bent door een organisator of het bestuur uitgeschreven voor [{activity}][1] en kan niet meer deelnemen aan deze activiteit.

Voor de terugbetaling van eventuele deelnamekosten, kan je contact opnemen met de organisatie.

Gedownloade tickets of tickets in je Google Wallet zijn niet meer geldig.

Namens {host},
De Digitale Commissie.

[1]: {activity_link}

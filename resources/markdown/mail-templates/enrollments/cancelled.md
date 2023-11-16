---
label: enrollment-cancelled
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

Je bent succesvol uitgeschreven voor [{activity}][1]. Mocht je alnog willen meedoen, dan moet je je [opnieuw aanmelden][1].

Eventueel gedownloade tickets of tickets in je Google Wallet zijn niet meer geldig.

Namens {host},
De Digitale Commissie.

[1]: {activity_link}

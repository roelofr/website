<?php

declare(strict_types=1);

namespace App\Services\Google\Traits;

use App\Enums\Models\GoogleWallet\ReviewStatus;
use App\Models\GoogleWallet\EventClass;
use Google_Service_Exception as ServiceException;
use Google_Service_Walletobjects_EventTicketClass  as EventTicketClass;
use Google_Service_Walletobjects_Eventticketclass_Resource as EventTicketClassResource;
use Illuminate\Support\Facades\URL;
use RuntimeException;

trait HandlesEventClasses
{
    use DeepComparesArraysAndObjects;

    abstract protected function getEventTicketClassApi(): EventTicketClassResource;

    /**
     * Writes a given EventClass to the Google Wallet API.
     */
    public function writeEventClass(EventClass $eventClass): EventClass
    {
        throw_unless(
            $eventClass->exists,
            LogicException::class,
            'The event class must exist before it can be created in Google Wallet.',
        );

        // Try to fetch the event class
        $existing = $this->findEventClass($eventClass);

        // If it exists, update it
        return $existing
            ? $this->updateEventClass($eventClass, $existing)
            : $this->createEventClass($eventClass, $existing);
    }

    /**
     * Returns an array of data that's expected to be present on the event class, to
     * allow for PATCH updates.
     */
    protected function buildTicketClassData(EventClass $eventClass): array
    {
        return [
            'id' => $eventClass->wallet_id,
            'eventId' => $eventClass->wallet_id,
            'eventName' => [
                'defaultValue' => [
                    'language' => 'nl',
                    'value' => $eventClass->name,
                ],
            ],
            'logo' => [
                'sourceUri' => [
                    'uri' => URL::secure(mix('images/logo-google-wallet.png')),
                ],
                'contentDescription' => [
                    'defaultValue' => [
                        'language' => 'nl',
                        'value' => 'Gumbo Millennium logo',
                    ],
                ],
            ],
            'venue' => array_filter([
                'name' => [
                    'defaultValue' => [
                        'language' => 'nl',
                        'value' => $eventClass->location_name,
                    ],
                ],
                'address' => $eventClass->location_address ? [
                    'defaultValue' => [
                        'language' => 'nl',
                        'value' => $eventClass->location_address,
                    ],
                ] : null,
            ]),
            'dateTime' => [
                'start' => $eventClass->start_time->toIso8601String(),
                'end' => $eventClass->end_time->toIso8601String(),
            ],
            'confirmationCodeLabel' => 'ORDER_NUMBER',
            'finePrint' => [
                'defaultValue' => [
                    'language' => 'nl',
                    'value' => 'Dit ticket is niet inwisselbaar voor geld. Voor alle voorwaarden tijdens het evenement, zie ons privacybeleid.',
                ],
                'translatedValues' => [
                    [
                        'language' => 'en',
                        'value' => 'This ticket is non-refundable. For all terms and conditions, please refer to our privacy policy.',
                    ],
                ],
            ],
            'issuerName' => 'Gumbo Millennium',
            'homepageUri' => [
                'uri' => URL::secure(route('home')),
            ],
            'countryCode' => 'NL',
            'heroImage' => $eventClass->hero_image ? [
                'sourceUri' => [
                    'uri' => URL::secure($eventClass->hero_image),
                ],
            ] : null,
            'hexBackgroundColor' => '#006b00',
            'securityAnimation' => [
                'animationType' => 'FOIL_SHIMMER',
            ],
            'viewUnlockRequirement' => 'UNLOCK_REQUIRED_TO_VIEW',
            'callbackOptions' => [
                'url' => URL::secure(route('api.webhooks.google-wallet')),
            ],
        ];
    }

    /**
     * Finds the Google Wallet EventTicketClass for a given EventClass.
     *
     * @throws ServiceException
     */
    protected function findEventClass(EventClass $eventClass): ?EventTicketClass
    {
        try {
            return $this->getEventTicketClassApi()->get($eventClass->wallet_id);
        } catch (ServiceException $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    protected function updateEventClass(EventClass $eventClass, EventTicketClass $existing): EventClass
    {
        $ticketClassData = $this->buildTicketClassData($eventClass);

        /** @var null|EventTicketClass $differences */
        $differences = $this->deepCompareArrayToObject($ticketClassData, $existing, EventTicketClass::class);

        if (empty($differences)) {
            return $eventClass;
        }

        $differences->reviewStatus = ReviewStatus::UnderReview;
        $result = $this->getEventTicketClassApi()->patch($existing->id, $differences);

        $eventClass->review_status = ReviewStatus::tryFrom($result->getReviewStatus()) ?? ReviewStatus::Unspecficied;
        $eventClass->review = $result->getReviewStatus();
        $eventClass->save();

        return $eventClass;
    }

    protected function createEventClass(EventClass $eventClass): EventClass
    {
        $ticketClassData = array_merge($this->buildTicketClassData($eventClass), [
            'reviewStatus' => $eventClass->review_status === ReviewStatus::Draft ? 'DRAFT' : 'UNDER_REVIEW',
        ]);

        try {
            $result = $this->getEventTicketClassApi()->insert(new EventTicketClass($ticketClassData));
        } catch (Serviceexception $exception) {
            report(new RuntimeException("Failed to createEventClass for event {$eventClass->id}: {$exception->getMessage()}", 0, $exception));

            throw $exception;
        }

        $eventClass->review_status = ReviewStatus::tryFrom($result->getReviewStatus()) ?? ReviewStatus::Unspecficied;
        $eventClass->review = $result->getReviewStatus();
        $eventClass->save();

        return $eventClass;
    }
}
